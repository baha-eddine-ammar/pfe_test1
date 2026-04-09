<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreServerRequest;
use App\Models\Server;
use App\Services\ServerMonitoringService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function __construct(
        private readonly ServerMonitoringService $serverMonitoringService,
    ) {
    }

    public function index(Request $request): View
    {
        $servers = Server::query()
            ->with('latestMetric')
            ->orderBy('name')
            ->get();

        return view('servers.index', [
            'servers' => $servers,
            'serverCards' => $this->serverMonitoringService->buildCards($servers),
            'user' => $request->user(),
        ]);
    }

    public function create(): View
    {
        return view('servers.create');
    }

    public function store(StoreServerRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $server = Server::query()->create([
            'name' => trim($validated['name']),
            'identifier' => Str::lower(trim($validated['identifier'])),
            'api_token' => Str::random(40),
        ]);

        return redirect()
            ->route('servers.show', $server)
            ->with('success', 'Server created successfully. Copy the API token below into your monitoring agent.');
    }

    public function show(Server $server): View
    {
        $server->load([
            'latestMetric',
            'metrics' => fn ($query) => $query->latest('created_at')->limit(10),
        ]);

        return view('servers.show', [
            'server' => $server,
            'serverCard' => $this->serverMonitoringService->buildCard($server),
            'recentMetrics' => $server->metrics,
        ]);
    }
}
