<?php

namespace App\Services;

class SupervisorService
{
    public function __construct(protected DockerService $docker) {}

    public function getContainers(): array
    {
        return config('supervisor.containers', []);
    }

    public function isValidContainer(string $container): bool
    {
        return array_key_exists($container, $this->getContainers());
    }

    public function getProcesses(string $container): array
    {
        $output = $this->docker->exec($container, 'supervisorctl status');
        $processes = [];

        foreach (explode("\n", trim($output)) as $line) {
            if (empty(trim($line))) {
                continue;
            }

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

    public function getAllProcesses(): array
    {
        $all = [];
        foreach (array_keys($this->getContainers()) as $container) {
            $processes = $this->getProcesses($container);
            foreach ($processes as $p) {
                $p['container'] = $container;
                $all[] = $p;
            }
        }
        return $all;
    }

    public function startProcess(string $container, string $process): string
    {
        return $this->docker->exec($container, 'supervisorctl start ' . escapeshellarg($process));
    }

    public function stopProcess(string $container, string $process): string
    {
        return $this->docker->exec($container, 'supervisorctl stop ' . escapeshellarg($process));
    }

    public function restartProcess(string $container, string $process): string
    {
        return $this->docker->exec($container, 'supervisorctl restart ' . escapeshellarg($process));
    }

    public function restartAll(string $container): string
    {
        return $this->docker->exec($container, 'supervisorctl restart all');
    }

    public function getConfigs(string $container): array
    {
        $confDir = $this->getContainers()[$container]['conf_dir'] ?? '/etc/supervisor/conf.d';
        $output = $this->docker->exec($container, 'find ' . escapeshellarg($confDir) . ' -name "*.conf" -type f 2>/dev/null | sort');
        $configs = [];

        foreach (explode("\n", trim($output)) as $file) {
            $file = trim($file);
            if (empty($file) || !str_ends_with($file, '.conf')) {
                continue;
            }
            $content = $this->docker->exec($container, 'cat ' . escapeshellarg($file));
            $configs[] = [
                'file' => basename($file),
                'path' => $file,
                'content' => $content,
            ];
        }

        return $configs;
    }

    public function createConfig(string $container, string $programName, array $settings): string
    {
        $confDir = $this->getContainers()[$container]['conf_dir'] ?? '/etc/supervisor/conf.d';
        $content = $this->buildConfigContent($programName, $settings);
        $filePath = $confDir . '/' . $programName . '.conf';

        $this->docker->exec($container, 'cat > ' . escapeshellarg($filePath) . ' << \'SUPERVISOR_EOF\'' . "\n" . $content . "\nSUPERVISOR_EOF");
        return $this->docker->exec($container, 'supervisorctl reread && supervisorctl update');
    }

    public function updateConfig(string $container, string $filename, string $programName, array $settings): string
    {
        $confDir = $this->getContainers()[$container]['conf_dir'] ?? '/etc/supervisor/conf.d';
        $content = $this->buildConfigContent($programName, $settings);
        $filePath = $confDir . '/' . $filename;

        $this->docker->exec($container, 'cat > ' . escapeshellarg($filePath) . ' << \'SUPERVISOR_EOF\'' . "\n" . $content . "\nSUPERVISOR_EOF");
        return $this->docker->exec($container, 'supervisorctl reread && supervisorctl update');
    }

    public function deleteConfig(string $container, string $filename): string
    {
        $confDir = $this->getContainers()[$container]['conf_dir'] ?? '/etc/supervisor/conf.d';
        $filePath = $confDir . '/' . $filename;

        // Extract program name from filename to stop it
        $programName = str_replace('.conf', '', $filename);
        $this->docker->exec($container, 'supervisorctl stop ' . escapeshellarg($programName) . ':* 2>/dev/null; supervisorctl stop ' . escapeshellarg($programName) . ' 2>/dev/null');
        $this->docker->exec($container, 'rm -f ' . escapeshellarg($filePath));
        return $this->docker->exec($container, 'supervisorctl reread && supervisorctl update');
    }

    public function buildConfigContent(string $programName, array $settings): string
    {
        $lines = ["[program:{$programName}]"];

        $keys = [
            'command', 'directory', 'user', 'numprocs',
            'autostart', 'autorestart', 'redirect_stderr',
            'stdout_logfile', 'stdout_logfile_maxbytes', 'stdout_logfile_backups',
            'stopwaitsecs', 'stopasgroup', 'killasgroup',
        ];

        foreach ($keys as $key) {
            if (!isset($settings[$key]) || $settings[$key] === '' || $settings[$key] === null) {
                continue;
            }

            $value = $settings[$key];

            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            $lines[] = "{$key}={$value}";
        }

        return implode("\n", $lines);
    }

    public function parseConfigContent(string $content): array
    {
        $settings = [];
        $programName = '';

        foreach (explode("\n", $content) as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, ';') || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^\[program:(.+)]$/', $line, $m)) {
                $programName = $m[1];
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                if (in_array($value, ['true', 'false'])) {
                    $value = $value === 'true';
                }

                $settings[$key] = $value;
            }
        }

        $settings['program_name'] = $programName;

        return $settings;
    }

    public function isValidProcessName(string $name): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9:_-]+$/', $name);
    }

    public function isValidFilename(string $filename): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_-]+\.conf$/', $filename);
    }
}
