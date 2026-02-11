<?php

namespace App\Services;

class DockerService
{
    protected string $socketPath = '/var/run/docker.sock';

    protected string $apiVersion = 'v1.43';

    protected array $allowedContainers = [
        'coliv_nginx',
        'coliv_beta',
        'coliv_websocket',
        'coliv_app',
        'coliv_supervisor',
        'coliv_redis',
        'coliv_redis_insight',
        'coliv_betamyadmin',
        'coliv_whatsapp',
    ];

    public function isAllowedContainer(string $name): bool
    {
        return in_array($name, $this->allowedContainers, true);
    }

    public function getAllowedContainers(): array
    {
        return $this->allowedContainers;
    }

    /**
     * Check if Docker socket is accessible.
     */
    public function isAvailable(): bool
    {
        return file_exists($this->socketPath) && is_readable($this->socketPath);
    }

    // -------------------------------------------------------------------------
    // Docker Engine API - HTTP helpers
    // -------------------------------------------------------------------------

    protected function apiRequest(string $method, string $endpoint, ?array $jsonBody = null, int $timeout = 30): array
    {
        $ch = curl_init();
        $url = "http://localhost/{$this->apiVersion}{$endpoint}";

        curl_setopt_array($ch, [
            CURLOPT_UNIX_SOCKET_PATH => $this->socketPath,
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);

        if ($jsonBody !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonBody));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['ok' => false, 'code' => 0, 'body' => null, 'error' => $error];
        }

        $json = json_decode($body, true);

        return [
            'ok' => $code >= 200 && $code < 300,
            'code' => $code,
            'body' => $json ?? $body,
            'error' => null,
        ];
    }

    // -------------------------------------------------------------------------
    // Container listing + stats (parallel)
    // -------------------------------------------------------------------------

    public function getStats(): array
    {
        $list = $this->apiRequest('GET', '/containers/json?all=true', null, 10);

        if (!$list['ok'] || !is_array($list['body'])) {
            return $this->offlineContainers();
        }

        $containers = [];
        $mh = curl_multi_init();
        $handles = [];

        foreach ($list['body'] as $c) {
            $name = ltrim($c['Names'][0] ?? '', '/');
            if (!$this->isAllowedContainer($name)) {
                continue;
            }

            $container = [
                'name' => $name,
                'id' => substr($c['Id'], 0, 12),
                'state' => $c['State'],
                'status' => $c['Status'] ?? $c['State'],
                'image' => $c['Image'] ?? '--',
            ];

            if ($c['State'] === 'running') {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_UNIX_SOCKET_PATH => $this->socketPath,
                    CURLOPT_URL => "http://localhost/{$this->apiVersion}/containers/{$name}/stats?stream=false",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 6,
                ]);
                curl_multi_add_handle($mh, $ch);
                $handles[$name] = $ch;
            } else {
                $container += $this->emptyStats();
            }

            $containers[$name] = $container;
        }

        // Execute all stats requests in parallel
        if (!empty($handles)) {
            do {
                $status = curl_multi_exec($mh, $active);
                if ($active) {
                    curl_multi_select($mh, 0.1);
                }
            } while ($active && $status === CURLM_OK);

            foreach ($handles as $name => $ch) {
                $raw = curl_multi_getcontent($ch);
                $stats = json_decode($raw, true);
                $containers[$name] += ($stats ? $this->parseRawStats($stats) : $this->emptyStats());
                curl_multi_remove_handle($mh, $ch);
                curl_close($ch);
            }
        }

        curl_multi_close($mh);

        // Add missing whitelisted containers as "not found"
        foreach ($this->allowedContainers as $name) {
            if (!isset($containers[$name])) {
                $containers[$name] = [
                    'name' => $name,
                    'id' => '--',
                    'state' => 'not found',
                    'status' => 'Not Found',
                    'image' => '--',
                ] + $this->emptyStats();
            }
        }

        // Sort by whitelist order
        $result = array_values($containers);
        usort($result, fn($a, $b) => array_search($a['name'], $this->allowedContainers) <=> array_search($b['name'], $this->allowedContainers));

        return $result;
    }

    // -------------------------------------------------------------------------
    // Restart
    // -------------------------------------------------------------------------

    public function restartContainer(string $name): array
    {
        if (!$this->isAllowedContainer($name)) {
            return ['success' => false, 'message' => 'Container not in whitelist.'];
        }

        $result = $this->apiRequest('POST', "/containers/{$name}/restart?t=10", null, 30);

        return [
            'success' => $result['ok'],
            'message' => $result['ok']
                ? "Container {$name} restarted successfully."
                : 'Restart failed: ' . (is_array($result['body']) ? ($result['body']['message'] ?? json_encode($result['body'])) : ($result['error'] ?? 'Unknown error')),
        ];
    }

    // -------------------------------------------------------------------------
    // Exec (create + start)
    // -------------------------------------------------------------------------

    public function exec(string $container, string $command): string
    {
        if (!$this->isAllowedContainer($container)) {
            return 'Error: Container not in whitelist.';
        }

        // 1. Create exec instance
        $create = $this->apiRequest('POST', "/containers/{$container}/exec", [
            'AttachStdout' => true,
            'AttachStderr' => true,
            'Tty' => true,
            'Cmd' => ['sh', '-c', $command],
        ]);

        if (!$create['ok'] || empty($create['body']['Id'])) {
            $msg = is_array($create['body']) ? ($create['body']['message'] ?? json_encode($create['body'])) : ($create['error'] ?? 'Unknown error');
            return "Error creating exec: {$msg}";
        }

        $execId = $create['body']['Id'];

        // 2. Start exec and capture output
        $start = $this->apiRequest('POST', "/exec/{$execId}/start", [
            'Detach' => false,
            'Tty' => true,
        ], 30);

        $body = $start['body'];

        if (is_array($body)) {
            return json_encode($body);
        }

        return trim((string) $body);
    }

    // -------------------------------------------------------------------------
    // Stats parsing
    // -------------------------------------------------------------------------

    protected function parseRawStats(array $s): array
    {
        // CPU percentage
        $cpuDelta = ($s['cpu_stats']['cpu_usage']['total_usage'] ?? 0)
                  - ($s['precpu_stats']['cpu_usage']['total_usage'] ?? 0);
        $sysDelta = ($s['cpu_stats']['system_cpu_usage'] ?? 0)
                  - ($s['precpu_stats']['system_cpu_usage'] ?? 0);
        $cpus = $s['cpu_stats']['online_cpus']
             ?? count($s['cpu_stats']['cpu_usage']['percpu_usage'] ?? [1]);
        $cpuPct = $sysDelta > 0 ? ($cpuDelta / $sysDelta) * $cpus * 100 : 0;

        // Memory
        $memUsage = $s['memory_stats']['usage'] ?? 0;
        $memCache = $s['memory_stats']['stats']['cache']
                 ?? ($s['memory_stats']['stats']['inactive_file'] ?? 0);
        $memActual = max(0, $memUsage - $memCache);
        $memLimit = $s['memory_stats']['limit'] ?? 1;
        $memPct = $memLimit > 0 ? ($memActual / $memLimit) * 100 : 0;

        // Network I/O
        $rx = $tx = 0;
        foreach ($s['networks'] ?? [] as $iface) {
            $rx += $iface['rx_bytes'] ?? 0;
            $tx += $iface['tx_bytes'] ?? 0;
        }

        // Block I/O
        $br = $bw = 0;
        foreach ($s['blkio_stats']['io_service_bytes_recursive'] ?? [] as $e) {
            $op = strtolower($e['op'] ?? '');
            if ($op === 'read') $br += $e['value'] ?? 0;
            if ($op === 'write') $bw += $e['value'] ?? 0;
        }

        return [
            'cpu' => number_format($cpuPct, 2) . '%',
            'mem_usage' => $this->fmtBytes($memActual) . ' / ' . $this->fmtBytes($memLimit),
            'mem_perc' => number_format($memPct, 2) . '%',
            'net_io' => $this->fmtBytes($rx) . ' / ' . $this->fmtBytes($tx),
            'block_io' => $this->fmtBytes($br) . ' / ' . $this->fmtBytes($bw),
            'pids' => (string) ($s['pids_stats']['current'] ?? 0),
        ];
    }

    protected function emptyStats(): array
    {
        return [
            'cpu' => '--',
            'mem_usage' => '--',
            'mem_perc' => '--',
            'net_io' => '--',
            'block_io' => '--',
            'pids' => '--',
        ];
    }

    protected function offlineContainers(): array
    {
        return array_map(fn($name) => [
            'name' => $name,
            'id' => '--',
            'state' => 'unknown',
            'status' => 'Docker socket unavailable',
            'image' => '--',
        ] + $this->emptyStats(), $this->allowedContainers);
    }

    protected function fmtBytes(int|float $bytes): string
    {
        $bytes = (int) $bytes;
        if ($bytes === 0) {
            return '0B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = min((int) floor(log(max(1, $bytes), 1024)), count($units) - 1);

        return round($bytes / pow(1024, $i), 1) . $units[$i];
    }
}
