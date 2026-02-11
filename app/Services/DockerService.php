<?php

namespace App\Services;

class DockerService
{
    protected array $allowedContainers = [
        'coliv_nginx',
        'coliv_beta',
        'coliv_websocket',
        'coliv_app',
        'coliv_supervisor',
        'coliv_redis',
        'coliv_redis_insight',
        'coliv_betamyadmin',
    ];

    public function isAllowedContainer(string $name): bool
    {
        return in_array($name, $this->allowedContainers, true);
    }

    public function getAllowedContainers(): array
    {
        return $this->allowedContainers;
    }

    public function getStats(): array
    {
        $format = '{{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.MemPerc}}\t{{.NetIO}}\t{{.BlockIO}}\t{{.PIDs}}\t{{.Status}}';
        $output = shell_exec('docker stats --no-stream --format ' . escapeshellarg($format) . ' 2>&1') ?? '';

        $containers = [];
        foreach (explode("\n", trim($output)) as $line) {
            if (empty($line)) {
                continue;
            }
            $parts = explode("\t", $line);
            if (count($parts) < 8) {
                continue;
            }

            $name = $parts[0];
            if (!$this->isAllowedContainer($name)) {
                continue;
            }

            $containers[] = [
                'name' => $name,
                'cpu' => $parts[1],
                'mem_usage' => $parts[2],
                'mem_perc' => $parts[3],
                'net_io' => $parts[4],
                'block_io' => $parts[5],
                'pids' => $parts[6],
                'status' => $parts[7],
            ];
        }

        // Add missing containers as "stopped"
        $running = array_column($containers, 'name');
        foreach ($this->allowedContainers as $name) {
            if (!in_array($name, $running, true)) {
                $containers[] = [
                    'name' => $name,
                    'cpu' => '--',
                    'mem_usage' => '--',
                    'mem_perc' => '--',
                    'net_io' => '--',
                    'block_io' => '--',
                    'pids' => '--',
                    'status' => $this->getContainerStatus($name),
                ];
            }
        }

        usort($containers, fn($a, $b) => array_search($a['name'], $this->allowedContainers) <=> array_search($b['name'], $this->allowedContainers));

        return $containers;
    }

    public function getContainerStatus(string $name): string
    {
        if (!$this->isAllowedContainer($name)) {
            return 'unknown';
        }

        $output = trim(shell_exec('docker inspect --format ' . escapeshellarg('{{.State.Status}}') . ' ' . escapeshellarg($name) . ' 2>&1') ?? '');

        return str_contains($output, 'Error') ? 'not found' : $output;
    }

    public function restartContainer(string $name): array
    {
        if (!$this->isAllowedContainer($name)) {
            return ['success' => false, 'message' => 'Container not in whitelist.'];
        }

        $output = shell_exec('docker restart ' . escapeshellarg($name) . ' 2>&1') ?? '';

        $success = trim($output) === $name;

        return [
            'success' => $success,
            'message' => $success ? "Container {$name} restarted successfully." : "Restart failed: {$output}",
        ];
    }

    public function exec(string $container, string $command): string
    {
        if (!$this->isAllowedContainer($container)) {
            return 'Error: Container not in whitelist.';
        }

        return trim(shell_exec('docker exec ' . escapeshellarg($container) . ' ' . $command . ' 2>&1') ?? '');
    }
}
