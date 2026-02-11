<?php

namespace App\Http\Controllers;

use App\Services\DockerService;
use Illuminate\Http\JsonResponse;

class SupervisorController extends Controller
{
    protected string $container = 'coliv_beta';

    public function __construct(protected DockerService $docker) {}

    public function index()
    {
        return view('supervisor.index', [
            'processes' => $this->getProcesses(),
            'configs' => $this->getConfigs(),
        ]);
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'processes' => $this->getProcesses(),
        ]);
    }

    public function restartProcess(string $process): JsonResponse
    {
        if (!$this->isValidProcessName($process)) {
            return response()->json(['success' => false, 'message' => 'Invalid process name.'], 422);
        }

        $output = $this->docker->exec($this->container, 'supervisorctl restart ' . escapeshellarg($process));

        $success = str_contains($output, 'started') || str_contains($output, 'ERROR (already started)');

        return response()->json([
            'success' => $success,
            'message' => $output ?: 'No output',
        ]);
    }

    public function restartAll(): JsonResponse
    {
        $output = $this->docker->exec($this->container, 'supervisorctl restart all');

        return response()->json([
            'success' => true,
            'message' => $output ?: 'All processes restarted.',
        ]);
    }

    protected function getProcesses(): array
    {
        $output = $this->docker->exec($this->container, 'supervisorctl status');
        $processes = [];

        foreach (explode("\n", trim($output)) as $line) {
            if (empty(trim($line))) {
                continue;
            }

            // Parse: process_name   STATE   pid XXXX, uptime X:XX:XX
            if (preg_match('/^(\S+)\s+(RUNNING|STOPPED|STARTING|BACKOFF|STOPPING|EXITED|FATAL|UNKNOWN)\s*(.*)/i', $line, $m)) {
                $processes[] = [
                    'name' => $m[1],
                    'state' => strtoupper($m[2]),
                    'info' => trim($m[3] ?? ''),
                ];
            }
        }

        return $processes;
    }

    protected function getConfigs(): array
    {
        $output = $this->docker->exec($this->container, 'find /etc/supervisor/conf.d-enabled /etc/supervisor/conf.d -name "*.conf" -type f 2>/dev/null | sort -u');
        $configs = [];

        foreach (explode("\n", trim($output)) as $file) {
            $file = trim($file);
            if (empty($file) || !str_ends_with($file, '.conf')) {
                continue;
            }
            $content = $this->docker->exec($this->container, 'cat ' . escapeshellarg($file));
            $configs[] = [
                'file' => basename($file),
                'path' => $file,
                'content' => $content,
            ];
        }

        return $configs;
    }

    protected function isValidProcessName(string $name): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9:_-]+$/', $name);
    }
}
