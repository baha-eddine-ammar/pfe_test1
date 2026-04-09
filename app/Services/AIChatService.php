<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Throwable;

class AIChatService
{
    public function reply(string $prompt, array $history = []): array
    {
        $baseUrl = config('services.groq.base_url');
        $model = config('services.groq.model');
        $apiKey = config('services.groq.api_key');

        if (! $apiKey || ! $baseUrl || ! $model) {
            return $this->fallback($prompt, 'Missing Groq configuration.');
        }

        try {
            $response = Http::baseUrl($baseUrl)
                ->withToken($apiKey)
                ->acceptJson()
                ->timeout(20)
                ->post('/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.3,
                    'messages' => $this->messages($prompt, $history),
                ]);

            if (! $response->successful()) {
                return $this->fallback($prompt, 'Groq request failed with status '.$response->status().'.');
            }

            $content = trim((string) data_get($response->json(), 'choices.0.message.content'));

            if ($content === '') {
                return $this->fallback($prompt, 'Groq returned an empty response.');
            }

            return [
                'body' => $content,
                'provider' => 'groq',
                'model' => $model,
                'status' => 'success',
                'error_message' => null,
            ];
        } catch (Throwable $exception) {
            return $this->fallback($prompt, $exception->getMessage());
        }
    }

    public function isConfigured(): bool
    {
        return (bool) config('services.groq.api_key');
    }

    private function messages(string $prompt, array $history): array
    {
        $historyMessages = Collection::make($history)
            ->filter(fn (array $message) => in_array($message['role'] ?? null, ['user', 'assistant'], true))
            ->take(-8)
            ->map(fn (array $message) => [
                'role' => $message['role'],
                'content' => (string) ($message['body'] ?? ''),
            ])
            ->filter(fn (array $message) => trim($message['content']) !== '')
            ->values()
            ->all();

        return [
            [
                'role' => 'system',
                'content' => 'You are an operations assistant for an IT server room supervision platform. Give concise, practical answers. Focus on reports, server health, maintenance, monitoring, power, cooling, and operational next steps. If you are unsure, say so clearly and suggest the safest next action.',
            ],
            ...$historyMessages,
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ];
    }

    private function fallback(string $prompt, string $reason): array
    {
        $normalized = strtolower($prompt);

        $body = match (true) {
            str_contains($normalized, 'temperature') || str_contains($normalized, 'heat')
                => 'This is a suggestion based on your server room data: review cooling performance, confirm airflow remains stable, and compare the latest room temperature trend before making changes.',
            str_contains($normalized, 'power') || str_contains($normalized, 'ups')
                => 'This is a suggestion based on your server room data: verify current power usage, inspect UPS load distribution, and confirm that no server is close to a critical capacity threshold.',
            str_contains($normalized, 'maintenance') || str_contains($normalized, 'task')
                => 'This is a suggestion based on your server room data: review open maintenance tasks first, confirm ownership, and update task status so the team has a clear operational view.',
            str_contains($normalized, 'report') || str_contains($normalized, 'summary')
                => 'This is a suggestion based on your server room data: generate a fresh report, compare warning and critical counts, and use the summary to guide the next maintenance or escalation step.',
            default
                => 'This is a suggestion based on your server room data: check the dashboard, review recent reports, and confirm whether any maintenance or server issue needs attention before escalating.',
        };

        return [
            'body' => $body,
            'provider' => 'fallback',
            'model' => 'deterministic',
            'status' => 'fallback',
            'error_message' => $reason,
        ];
    }
}
