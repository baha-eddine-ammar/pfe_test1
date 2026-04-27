<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TemperaturePredictionService
{
    public function predict(float $temperature): array
    {
        $response = Http::post('http://127.0.0.1:8001/predict', [
            'temperature' => $temperature
        ]);

        if ($response->failed()) {
            return [
                'prediction' => null,
                'failure_probability' => null,
                'risk_level' => 'unknown'
            ];
        }

        return $response->json();
    }
}
