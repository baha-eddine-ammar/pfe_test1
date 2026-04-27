<?php

/*
|--------------------------------------------------------------------------
| File Purpose
|--------------------------------------------------------------------------
| This service collects telemetry from the local Windows workstation that is
| running the Laravel app, then converts that telemetry into one dashboard-
| ready server panel.
|
| Why this file exists:
| The dashboard now shows one consolidated "Server Section" instead of the
| previous multi-card demo fleet. The data shown there should reflect the
| current PC in near real time.
|
| When this file is used:
| - When the dashboard page is first rendered
| - When the dashboard polls for fresh PC status updates
*/

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

class LocalSystemStatusService
{
    public function dashboardCard(): array
    {
        $snapshot = $this->snapshot();
        $lastSeenAt = Carbon::parse($snapshot['sampledAt']);

        $cpuProgress = $this->clampProgress($snapshot['cpuPercent']);
        $ramProgress = $this->percentage($snapshot['ramUsedMb'], $snapshot['ramTotalMb']);
        $diskProgress = $this->percentage($snapshot['diskUsedGb'], $snapshot['diskTotalGb']);

        $storageUsedGb = max(0, $snapshot['storageTotalGb'] - $snapshot['storageFreeGb']);
        $storageProgress = $this->percentage($storageUsedGb, $snapshot['storageTotalGb']);
        $storageFreePercent = max(0, 100 - $storageProgress);
        $storageState = $this->storageState($storageProgress);

        $temperatureProgress = $snapshot['temperatureC'] !== null
            ? $this->temperatureProgress($snapshot['temperatureC'])
            : 0;
        $temperatureState = $snapshot['temperatureC'] !== null
            ? $this->temperatureState($snapshot['temperatureC'])
            : 'Not exposed';

        $networkProgress = $this->networkProgress($snapshot);

        [$status, $statusColor] = $this->resolveStatus(
            $cpuProgress,
            $ramProgress,
            $diskProgress,
            $storageProgress,
            $snapshot['temperatureC'],
            $snapshot['networkConnected'],
        );

        return [
            'id' => 'local-workstation',
            'name' => $snapshot['computerName'],
            'identifier' => 'Local workstation telemetry',
            'status' => $status,
            'statusColor' => $statusColor,
            'lastSeenAt' => $lastSeenAt->toDateTimeString(),
            'lastSeenLabel' => 'Updated '.$lastSeenAt->format('H:i:s'),
            'narrative' => $this->narrative($status, $snapshot),
            'metrics' => [
                [
                    'key' => 'cpu',
                    'label' => 'CPU usage',
                    'value' => $this->formatPercent($snapshot['cpuPercent']),
                    'progress' => $cpuProgress,
                    'color' => 'cyan',
                    'footerLabel' => 'Utilization',
                    'progressLabel' => $cpuProgress.'%',
                ],
                [
                    'key' => 'ram',
                    'label' => 'RAM usage',
                    'value' => $this->formatMemory($snapshot['ramUsedMb']).'/'.$this->formatMemory($snapshot['ramTotalMb']),
                    'progress' => $ramProgress,
                    'color' => 'violet',
                    'footerLabel' => 'Utilization',
                    'progressLabel' => $ramProgress.'%',
                ],
                [
                    'key' => 'disk',
                    'label' => 'Disk usage',
                    'value' => $this->formatStorage($snapshot['diskUsedGb']).'/'.$this->formatStorage($snapshot['diskTotalGb']),
                    'progress' => $diskProgress,
                    'color' => 'pink',
                    'footerLabel' => 'Utilization',
                    'progressLabel' => $diskProgress.'%',
                ],
                [
                    'key' => 'storage',
                    'label' => 'Storage status',
                    'value' => $storageState.' - '.$this->formatStorage($snapshot['storageFreeGb']).' free',
                    'progress' => $storageProgress,
                    'color' => 'emerald',
                    'footerLabel' => 'Free space',
                    'progressLabel' => $storageFreePercent.'% free',
                ],
                [
                    'key' => 'temperature',
                    'label' => 'CPU temperature',
                    'value' => $snapshot['temperatureC'] !== null
                        ? $this->formatNumber($snapshot['temperatureC']).' C'
                        : 'Unavailable',
                    'progress' => $temperatureProgress,
                    'color' => 'amber',
                    'footerLabel' => 'Sensor',
                    'progressLabel' => $temperatureState,
                ],
                [
                    'key' => 'network',
                    'label' => 'Network information',
                    'value' => $this->networkValue($snapshot),
                    'progress' => $networkProgress,
                    'color' => 'emerald',
                    'footerLabel' => $snapshot['networkName'] !== '' ? $snapshot['networkName'] : 'Link',
                    'progressLabel' => $snapshot['networkConnected']
                        ? ($snapshot['networkIpv4'] ?? 'Connected')
                        : 'Offline',
                ],
            ],
        ];
    }

    private function snapshot(): array
    {
        if (app()->environment('testing')) {
            return $this->fallbackSnapshot('LOCAL-WORKSTATION');
        }

        $computerName = php_uname('n');

        if (PHP_OS_FAMILY !== 'Windows') {
            return $this->recoverSnapshot($computerName);
        }

        if ($snapshot = $this->collectWithPowerShell()) {
            return $snapshot;
        }

        if ($snapshot = $this->collectWithPython()) {
            return $snapshot;
        }

        return $this->recoverSnapshot($computerName);
    }

    private function collectWithPowerShell(): ?array
    {
        $powerShellBinary = $this->powerShellBinary();

        if ($powerShellBinary === null) {
            $this->reportCollectorFailure(
                'powershell',
                'Local system telemetry could not find a PowerShell binary.'
            );

            return null;
        }

        try {
            $result = Process::timeout(15)->run([
                $powerShellBinary,
                '-NoProfile',
                '-NonInteractive',
                '-ExecutionPolicy',
                'Bypass',
                '-Command',
                $this->powershellScript(),
            ]);

            if ($result->failed() || trim($result->output()) === '') {
                $this->reportCollectorFailure(
                    'powershell',
                    'Local system telemetry PowerShell collector failed; trying Python fallback.',
                    [
                        'exit_code' => $result->exitCode(),
                        'error_output' => $this->normalizeProcessError($result->errorOutput()),
                    ]
                );

                return null;
            }

            return $this->rememberCollectedSnapshot(
                json_decode($result->output(), true, 512, JSON_THROW_ON_ERROR)
            );
        } catch (Throwable $exception) {
            $this->reportCollectorFailure(
                'powershell',
                'Local system telemetry PowerShell collector threw an exception; trying Python fallback.',
                [
                    'message' => $exception->getMessage(),
                ]
            );

            return null;
        }
    }

    private function collectWithPython(): ?array
    {
        $scriptPath = $this->pythonTelemetryScriptPath();

        if ($scriptPath === null) {
            return null;
        }

        $lastFailure = null;

        foreach ($this->pythonCommandCandidates() as $candidate) {
            try {
                $result = Process::timeout(15)->run([
                    ...$candidate,
                    $scriptPath,
                ]);

                if ($result->successful() && trim($result->output()) !== '') {
                    return $this->rememberCollectedSnapshot(
                        json_decode($result->output(), true, 512, JSON_THROW_ON_ERROR)
                    );
                }

                $lastFailure = [
                    'command' => implode(' ', $candidate),
                    'exit_code' => $result->exitCode(),
                    'error_output' => $this->normalizeProcessError($result->errorOutput()),
                ];
            } catch (Throwable $exception) {
                $lastFailure = [
                    'command' => implode(' ', $candidate),
                    'message' => $exception->getMessage(),
                ];
            }
        }

        $this->reportCollectorFailure(
            'python',
            'Local system telemetry Python fallback collector failed.',
            $lastFailure ?? []
        );

        return null;
    }

    private function powerShellBinary(): ?string
    {
        $candidates = [
            'C:\Windows\Sysnative\WindowsPowerShell\v1.0\powershell.exe',
            'C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe',
            'C:\Windows\SysWOW64\WindowsPowerShell\v1.0\powershell.exe',
            'powershell.exe',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === 'powershell.exe' || is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function pythonTelemetryScriptPath(): ?string
    {
        $path = base_path('ml_service/workstation_telemetry.py');

        if (is_file($path)) {
            return $path;
        }

        $this->reportCollectorFailure(
            'python',
            'Local system telemetry Python fallback script is missing.',
            ['path' => $path]
        );

        return null;
    }

    private function pythonCommandCandidates(): array
    {
        $candidates = [];
        $configuredBinary = trim((string) env('LOCAL_TELEMETRY_PYTHON_BIN', ''));

        if ($configuredBinary !== '') {
            $candidates[] = [$configuredBinary];
        }

        $venvBinary = base_path('venv\Scripts\python.exe');
        if (is_file($venvBinary)) {
            $candidates[] = [$venvBinary];
        }

        $localAppData = getenv('LOCALAPPDATA');
        if (is_string($localAppData) && $localAppData !== '') {
            foreach (glob($localAppData.'\Python\pythoncore-*\python.exe') ?: [] as $pythonBinary) {
                if (is_file($pythonBinary)) {
                    $candidates[] = [$pythonBinary];
                }
            }

            $windowsAppsBinary = $localAppData.'\Microsoft\WindowsApps\python.exe';
            if (is_file($windowsAppsBinary)) {
                $candidates[] = [$windowsAppsBinary];
            }
        }

        $candidates[] = ['python'];
        $candidates[] = ['py', '-3'];
        $candidates[] = ['py'];

        return collect($candidates)
            ->unique(fn (array $command): string => implode('|', $command))
            ->values()
            ->all();
    }

    private function recoverSnapshot(string $computerName): array
    {
        $cachedSnapshot = Cache::get($this->snapshotCacheKey());

        if (is_array($cachedSnapshot)) {
            return $this->normalizeSnapshot($cachedSnapshot);
        }

        return $this->unavailableSnapshot($computerName);
    }

    private function rememberSnapshot(array $snapshot): void
    {
        Cache::put($this->snapshotCacheKey(), $snapshot, now()->addMinutes(5));
    }

    private function rememberCollectedSnapshot(array $snapshot): array
    {
        $normalized = $this->withRealtimeRates($this->normalizeSnapshot($snapshot));

        $this->rememberSnapshot($normalized);

        return $normalized;
    }

    private function snapshotCacheKey(): string
    {
        return 'local-system-status.latest-success';
    }

    private function powershellScript(): string
    {
        return <<<'POWERSHELL'
$cpuPerf = Get-CimInstance Win32_PerfFormattedData_PerfOS_Processor -Filter "Name='_Total'" -ErrorAction SilentlyContinue | Select-Object -First 1
$cpuAverage = if ($cpuPerf -and $null -ne $cpuPerf.PercentProcessorTime) {
    [double] $cpuPerf.PercentProcessorTime
} else {
    (Get-CimInstance Win32_Processor | Measure-Object -Property LoadPercentage -Average).Average
}
$os = Get-CimInstance Win32_OperatingSystem
$fixedDrives = Get-CimInstance Win32_LogicalDisk -Filter "DriveType=3"
$systemDriveId = $env:SystemDrive
$primaryDrive = $fixedDrives | Where-Object DeviceID -eq $systemDriveId | Select-Object -First 1
if (-not $primaryDrive) {
    $primaryDrive = $fixedDrives | Select-Object -First 1
}

$storageTotal = ($fixedDrives | Measure-Object -Property Size -Sum).Sum
$storageFree = ($fixedDrives | Measure-Object -Property FreeSpace -Sum).Sum

$thermalSample = Get-CimInstance -Namespace root/wmi -ClassName MSAcpi_ThermalZoneTemperature -ErrorAction SilentlyContinue | Select-Object -First 1
$temperatureC = $null
if ($thermalSample -and $thermalSample.CurrentTemperature) {
    $temperatureC = [Math]::Round(($thermalSample.CurrentTemperature / 10) - 273.15, 1)
    if ($temperatureC -lt -30 -or $temperatureC -gt 150) {
        $temperatureC = $null
    }
}

$activeAdapter = $null
if (Get-Command Get-NetAdapter -ErrorAction SilentlyContinue) {
    $activeAdapter = Get-NetAdapter -ErrorAction SilentlyContinue | Where-Object Status -eq 'Up' | Select-Object -First 1
}

if (-not $activeAdapter) {
    $legacyAdapter = Get-CimInstance Win32_NetworkAdapter -ErrorAction SilentlyContinue | Where-Object NetEnabled -eq $true | Select-Object -First 1

    if ($legacyAdapter) {
        $networkName = if ($legacyAdapter.NetConnectionID) { $legacyAdapter.NetConnectionID } else { $legacyAdapter.Name }
        $networkSpeed = if ($legacyAdapter.Speed) { [Math]::Round(([double] $legacyAdapter.Speed) / 1Mb, 0) } else { $null }
    }
}

if ($activeAdapter) {
    $networkName = if ($activeAdapter.Name) { $activeAdapter.Name } else { $activeAdapter.InterfaceDescription }
    $networkSpeed = $null

    if ($activeAdapter.LinkSpeed) {
        $speedText = [string] $activeAdapter.LinkSpeed
        if ($speedText -match '([0-9.]+)\s*(Gbps|Mbps)') {
            $networkSpeed = [double] $matches[1]
            if ($matches[2] -eq 'Gbps') {
                $networkSpeed = $networkSpeed * 1000
            }
            $networkSpeed = [Math]::Round($networkSpeed, 0)
        }
    }
}

$networkIpv4 = $null
$networkMac = $null
$networkRxBytes = $null
$networkTxBytes = $null
if ($activeAdapter) {
    $networkMac = $activeAdapter.MacAddress

    if (Get-Command Get-NetIPConfiguration -ErrorAction SilentlyContinue) {
        $ipConfig = Get-NetIPConfiguration -InterfaceIndex $activeAdapter.ifIndex -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($ipConfig -and $ipConfig.IPv4Address -and $ipConfig.IPv4Address.IPAddress) {
            $networkIpv4 = $ipConfig.IPv4Address.IPAddress
        }
    }

    if (Get-Command Get-NetAdapterStatistics -ErrorAction SilentlyContinue) {
        $adapterStats = Get-NetAdapterStatistics -Name $activeAdapter.Name -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($adapterStats) {
            $networkRxBytes = [double] $adapterStats.ReceivedBytes
            $networkTxBytes = [double] $adapterStats.SentBytes
        }
    }
} elseif ($legacyAdapter) {
    $networkMac = $legacyAdapter.MACAddress
}

$result = @{
    computerName = $env:COMPUTERNAME
    cpuPercent = [Math]::Round([double] $cpuAverage, 1)
    ramTotalMb = [Math]::Round(([double] $os.TotalVisibleMemorySize) / 1024, 0)
    ramUsedMb = [Math]::Round((([double] $os.TotalVisibleMemorySize) - ([double] $os.FreePhysicalMemory)) / 1024, 0)
    diskTotalGb = if ($primaryDrive -and $primaryDrive.Size) { [Math]::Round(([double] $primaryDrive.Size) / 1GB, 1) } else { 0 }
    diskUsedGb = if ($primaryDrive -and $primaryDrive.Size) { [Math]::Round((([double] $primaryDrive.Size) - ([double] $primaryDrive.FreeSpace)) / 1GB, 1) } else { 0 }
    storageTotalGb = if ($storageTotal) { [Math]::Round(([double] $storageTotal) / 1GB, 1) } else { 0 }
    storageFreeGb = if ($storageFree) { [Math]::Round(([double] $storageFree) / 1GB, 1) } else { 0 }
    temperatureC = $temperatureC
    networkConnected = [bool] ($activeAdapter -or $legacyAdapter)
    networkName = if ($networkName) { $networkName } else { 'Disconnected' }
    networkSpeedMbps = $networkSpeed
    networkIpv4 = $networkIpv4
    networkMac = $networkMac
    networkRxBytes = $networkRxBytes
    networkTxBytes = $networkTxBytes
    sampledAt = (Get-Date).ToString('o')
}

$result | ConvertTo-Json -Compress
POWERSHELL;
    }

    private function normalizeSnapshot(array $snapshot): array
    {
        return [
            'computerName' => (string) ($snapshot['computerName'] ?? php_uname('n')),
            'cpuPercent' => (float) ($snapshot['cpuPercent'] ?? 0),
            'ramUsedMb' => (int) ($snapshot['ramUsedMb'] ?? 0),
            'ramTotalMb' => max(1, (int) ($snapshot['ramTotalMb'] ?? 1)),
            'diskUsedGb' => (float) ($snapshot['diskUsedGb'] ?? 0),
            'diskTotalGb' => max(1, (float) ($snapshot['diskTotalGb'] ?? 1)),
            'storageFreeGb' => max(0, (float) ($snapshot['storageFreeGb'] ?? 0)),
            'storageTotalGb' => max(1, (float) ($snapshot['storageTotalGb'] ?? 1)),
            'temperatureC' => isset($snapshot['temperatureC']) && $snapshot['temperatureC'] !== ''
                ? (float) $snapshot['temperatureC']
                : null,
            'networkConnected' => (bool) ($snapshot['networkConnected'] ?? false),
            'networkName' => (string) ($snapshot['networkName'] ?? 'Disconnected'),
            'networkSpeedMbps' => isset($snapshot['networkSpeedMbps']) && $snapshot['networkSpeedMbps'] !== ''
                ? (int) round((float) $snapshot['networkSpeedMbps'])
                : null,
            'networkIpv4' => isset($snapshot['networkIpv4']) && $snapshot['networkIpv4'] !== ''
                ? (string) $snapshot['networkIpv4']
                : null,
            'networkMac' => isset($snapshot['networkMac']) && $snapshot['networkMac'] !== ''
                ? (string) $snapshot['networkMac']
                : null,
            'networkRxBytes' => isset($snapshot['networkRxBytes']) && $snapshot['networkRxBytes'] !== ''
                ? (float) $snapshot['networkRxBytes']
                : null,
            'networkTxBytes' => isset($snapshot['networkTxBytes']) && $snapshot['networkTxBytes'] !== ''
                ? (float) $snapshot['networkTxBytes']
                : null,
            'networkDownloadMbps' => isset($snapshot['networkDownloadMbps']) && $snapshot['networkDownloadMbps'] !== ''
                ? max(0, (float) $snapshot['networkDownloadMbps'])
                : null,
            'networkUploadMbps' => isset($snapshot['networkUploadMbps']) && $snapshot['networkUploadMbps'] !== ''
                ? max(0, (float) $snapshot['networkUploadMbps'])
                : null,
            'sampledAt' => (string) ($snapshot['sampledAt'] ?? now()->toIso8601String()),
        ];
    }

    private function withRealtimeRates(array $snapshot): array
    {
        $previous = Cache::get($this->snapshotCacheKey());

        if (
            ! is_array($previous)
            || $snapshot['networkRxBytes'] === null
            || $snapshot['networkTxBytes'] === null
            || ! isset($previous['networkRxBytes'], $previous['networkTxBytes'], $previous['sampledAt'])
        ) {
            return $snapshot;
        }

        $currentSampledAt = Carbon::parse($snapshot['sampledAt']);
        $previousSampledAt = Carbon::parse((string) $previous['sampledAt']);
        $seconds = max(1, $previousSampledAt->diffInMilliseconds($currentSampledAt) / 1000);

        $receivedBytes = max(0, $snapshot['networkRxBytes'] - (float) $previous['networkRxBytes']);
        $sentBytes = max(0, $snapshot['networkTxBytes'] - (float) $previous['networkTxBytes']);

        $snapshot['networkDownloadMbps'] = round(($receivedBytes * 8) / $seconds / 1_000_000, 2);
        $snapshot['networkUploadMbps'] = round(($sentBytes * 8) / $seconds / 1_000_000, 2);

        return $snapshot;
    }

    private function fallbackSnapshot(string $computerName): array
    {
        return [
            'computerName' => $computerName !== '' ? $computerName : 'LOCAL-WORKSTATION',
            'cpuPercent' => 0,
            'ramUsedMb' => 0,
            'ramTotalMb' => 1,
            'diskUsedGb' => 0,
            'diskTotalGb' => 1,
            'storageFreeGb' => 0,
            'storageTotalGb' => 1,
            'temperatureC' => null,
            'networkConnected' => false,
            'networkName' => 'Disconnected',
            'networkSpeedMbps' => null,
            'networkIpv4' => null,
            'networkMac' => null,
            'networkRxBytes' => null,
            'networkTxBytes' => null,
            'networkDownloadMbps' => null,
            'networkUploadMbps' => null,
            'sampledAt' => now()->toIso8601String(),
        ];
    }

    private function unavailableSnapshot(string $computerName): array
    {
        return [
            'computerName' => $computerName !== '' ? $computerName : 'LOCAL-WORKSTATION',
            'cpuPercent' => 0,
            'ramUsedMb' => 0,
            'ramTotalMb' => 1,
            'diskUsedGb' => 0,
            'diskTotalGb' => 1,
            'storageFreeGb' => 0,
            'storageTotalGb' => 1,
            'temperatureC' => null,
            'networkConnected' => false,
            'networkName' => 'Disconnected',
            'networkSpeedMbps' => null,
            'networkIpv4' => null,
            'networkMac' => null,
            'networkRxBytes' => null,
            'networkTxBytes' => null,
            'networkDownloadMbps' => null,
            'networkUploadMbps' => null,
            'sampledAt' => now()->toIso8601String(),
        ];
    }

    private function normalizeProcessError(string $errorOutput): string
    {
        $trimmed = trim($errorOutput);

        if ($trimmed === '') {
            return $trimmed;
        }

        if (str_contains($trimmed, "\0")) {
            $decoded = @mb_convert_encoding($trimmed, 'UTF-8', 'UTF-16LE');

            if (is_string($decoded) && trim($decoded) !== '') {
                return trim($decoded);
            }
        }

        return $trimmed;
    }

    private function reportCollectorFailure(string $collector, string $message, array $context = []): void
    {
        $cacheKey = 'local-system-status.failure-log.'.$collector;

        if (Cache::add($cacheKey, true, now()->addMinutes(15))) {
            Log::warning($message, $context);
        }
    }

    private function resolveStatus(
        int $cpuProgress,
        int $ramProgress,
        int $diskProgress,
        int $storageProgress,
        ?float $temperatureC,
        bool $networkConnected,
    ): array {
        if (! $networkConnected && $cpuProgress === 0 && $ramProgress === 0 && $diskProgress === 0) {
            return ['Offline', 'slate'];
        }

        if (
            $cpuProgress >= 90
            || $ramProgress >= 90
            || $diskProgress >= 90
            || $storageProgress >= 92
            || ($temperatureC !== null && $temperatureC >= 85)
        ) {
            return ['Critical', 'pink'];
        }

        if (
            $cpuProgress >= 75
            || $ramProgress >= 75
            || $diskProgress >= 80
            || $storageProgress >= 85
            || ! $networkConnected
            || ($temperatureC !== null && $temperatureC >= 70)
        ) {
            return ['Warning', 'amber'];
        }

        return ['Live', 'emerald'];
    }

    private function storageState(int $storageProgress): string
    {
        if ($storageProgress >= 92) {
            return 'Low space';
        }

        if ($storageProgress >= 85) {
            return 'Watch';
        }

        return 'Healthy';
    }

    private function temperatureState(float $temperatureC): string
    {
        if ($temperatureC >= 85) {
            return 'Critical';
        }

        if ($temperatureC >= 70) {
            return 'Warm';
        }

        return 'Stable';
    }

    private function temperatureProgress(float $temperatureC): int
    {
        return $this->clampProgress((($temperatureC - 20) / 70) * 100);
    }

    private function narrative(string $status, array $snapshot): string
    {
        $intro = match ($status) {
            'Offline' => 'This workstation telemetry feed is currently unavailable, so the local host should be verified before relying on these values.',
            'Critical' => 'This workstation is under heavy load and needs immediate attention.',
            'Warning' => 'This workstation is healthy enough to operate, but one or more resources should be watched.',
            default => 'This workstation is reporting normal live telemetry and remains within expected operating ranges.',
        };

        $temperatureNote = $snapshot['temperatureC'] === null
            ? 'Temperature is shown only when Windows exposes a readable thermal sensor.'
            : 'Temperature is currently being sampled from the local machine.';

        $networkNote = $snapshot['networkConnected']
            ? 'Network connectivity is active and the adapter is reporting link status.'
            : 'No active network adapter is currently reporting as connected.';

        return $intro.' '.$temperatureNote.' '.$networkNote;
    }

    private function networkValue(array $snapshot): string
    {
        if (! $snapshot['networkConnected']) {
            return 'Disconnected';
        }

        if ($snapshot['networkDownloadMbps'] !== null || $snapshot['networkUploadMbps'] !== null) {
            return 'Down '.$this->formatNetworkRate((float) ($snapshot['networkDownloadMbps'] ?? 0))
                .' / Up '.$this->formatNetworkRate((float) ($snapshot['networkUploadMbps'] ?? 0));
        }

        $details = [];

        if ($snapshot['networkSpeedMbps'] !== null) {
            $details[] = $snapshot['networkSpeedMbps'].' Mbps';
        }

        $name = $snapshot['networkName'] !== ''
            ? $snapshot['networkName']
            : 'Connected';

        if ($details !== []) {
            return $name.' - '.implode(' - ', $details);
        }

        return $name.' - Link active';
    }

    private function networkProgress(array $snapshot): int
    {
        if (! $snapshot['networkConnected']) {
            return 0;
        }

        $downloadMbps = (float) ($snapshot['networkDownloadMbps'] ?? 0);
        $uploadMbps = (float) ($snapshot['networkUploadMbps'] ?? 0);
        $transferMbps = $downloadMbps + $uploadMbps;

        if ($snapshot['networkSpeedMbps'] !== null && $snapshot['networkSpeedMbps'] > 0) {
            if ($transferMbps <= 0) {
                return 3;
            }

            return $this->clampProgress(($transferMbps / $snapshot['networkSpeedMbps']) * 100);
        }

        return $transferMbps > 0 ? 10 : 3;
    }

    private function formatNetworkRate(float $value): string
    {
        if ($value < 0.01) {
            return '0 Mbps';
        }

        return $this->formatNumber($value).' Mbps';
    }

    private function percentage(float|int $used, float|int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return $this->clampProgress(($used / $total) * 100);
    }

    private function clampProgress(float|int $value): int
    {
        return (int) round(max(0, min(100, $value)));
    }

    private function formatPercent(float $value): string
    {
        return $this->formatNumber($value).'%';
    }

    private function formatMemory(int $valueInMb): string
    {
        return $this->formatNumber($valueInMb / 1024).' GB';
    }

    private function formatStorage(float $valueInGb): string
    {
        return $this->formatNumber($valueInGb).' GB';
    }

    private function formatNumber(float $value): string
    {
        $formatted = number_format($value, 1, '.', '');

        return str_ends_with($formatted, '.0')
            ? substr($formatted, 0, -2)
            : $formatted;
    }
}
