<?php

namespace App\Http\Controllers;

use App\Services\DockerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuickCommandController extends Controller
{
    protected array $commands = [
        // Cache & Optimization
        'beta_optimize' => [
            'label' => 'Optimize (Beta)',
            'command' => 'php artisan optimize',
            'container' => 'coliv_beta',
            'category' => 'Cache & Optimization',
            'danger' => 'safe',
        ],
        'beta_clear_optimize' => [
            'label' => 'Clear Optimization (Beta)',
            'command' => 'php artisan optimize:clear',
            'container' => 'coliv_beta',
            'category' => 'Cache & Optimization',
            'danger' => 'safe',
        ],
        'beta_clear_cache' => [
            'label' => 'Clear Cache (Beta)',
            'command' => 'php artisan cache:clear',
            'container' => 'coliv_beta',
            'category' => 'Cache & Optimization',
            'danger' => 'safe',
        ],
        'gateway_optimize' => [
            'label' => 'Optimize (Gateway)',
            'command' => 'php artisan optimize',
            'container' => 'coliv_app',
            'category' => 'Cache & Optimization',
            'danger' => 'safe',
        ],
        'gateway_clear_optimize' => [
            'label' => 'Clear Optimization (Gateway)',
            'command' => 'php artisan optimize:clear',
            'container' => 'coliv_app',
            'category' => 'Cache & Optimization',
            'danger' => 'safe',
        ],
        'gateway_clear_cache' => [
            'label' => 'Clear Cache (Gateway)',
            'command' => 'php artisan cache:clear',
            'container' => 'coliv_app',
            'category' => 'Cache & Optimization',
            'danger' => 'safe',
        ],
        'redis_flush' => [
            'label' => 'Flush Redis DB',
            'command' => 'redis-cli FLUSHDB',
            'container' => 'coliv_redis',
            'category' => 'Cache & Optimization',
            'danger' => 'warning',
        ],

        // Queue
        'queue_restart' => [
            'label' => 'Restart Queue Workers',
            'command' => 'php artisan queue:restart',
            'container' => 'coliv_beta',
            'category' => 'Queue',
            'danger' => 'warning',
        ],
        'queue_retry_failed' => [
            'label' => 'Retry Failed Jobs',
            'command' => 'php artisan queue:retry all',
            'container' => 'coliv_beta',
            'category' => 'Queue',
            'danger' => 'warning',
        ],
        'queue_flush_failed' => [
            'label' => 'Flush Failed Jobs',
            'command' => 'php artisan queue:flush',
            'container' => 'coliv_beta',
            'category' => 'Queue',
            'danger' => 'danger',
        ],
        'queue_size' => [
            'label' => 'Queue Size',
            'command' => 'php artisan queue:monitor default',
            'container' => 'coliv_beta',
            'category' => 'Queue',
            'danger' => 'safe',
        ],

        // Maintenance
        'beta_maintenance_on' => [
            'label' => 'Maintenance ON (Beta)',
            'command' => 'php artisan down',
            'container' => 'coliv_beta',
            'category' => 'Maintenance',
            'danger' => 'danger',
        ],
        'beta_maintenance_off' => [
            'label' => 'Maintenance OFF (Beta)',
            'command' => 'php artisan up',
            'container' => 'coliv_beta',
            'category' => 'Maintenance',
            'danger' => 'warning',
        ],
        'beta_migrate_status' => [
            'label' => 'Migration Status',
            'command' => 'php artisan migrate:status',
            'container' => 'coliv_beta',
            'category' => 'Maintenance',
            'danger' => 'safe',
        ],
        'beta_storage_link' => [
            'label' => 'Storage Link',
            'command' => 'php artisan storage:link',
            'container' => 'coliv_beta',
            'category' => 'Maintenance',
            'danger' => 'safe',
        ],

        // Container Management
        'restart_nginx' => [
            'label' => 'Restart Nginx',
            'command' => null,
            'container' => 'coliv_nginx',
            'category' => 'Container Management',
            'danger' => 'danger',
            'docker_restart' => true,
        ],
        'nginx_reload' => [
            'label' => 'Nginx Reload',
            'command' => 'nginx -s reload',
            'container' => 'coliv_nginx',
            'category' => 'Container Management',
            'danger' => 'warning',
        ],
        'nginx_test_config' => [
            'label' => 'Nginx Test Config',
            'command' => 'nginx -t',
            'container' => 'coliv_nginx',
            'category' => 'Container Management',
            'danger' => 'safe',
        ],
        'restart_beta' => [
            'label' => 'Restart Beta',
            'command' => null,
            'container' => 'coliv_beta',
            'category' => 'Container Management',
            'danger' => 'danger',
            'docker_restart' => true,
        ],
        'restart_websocket' => [
            'label' => 'Restart WebSocket',
            'command' => null,
            'container' => 'coliv_websocket',
            'category' => 'Container Management',
            'danger' => 'danger',
            'docker_restart' => true,
        ],
        'restart_app' => [
            'label' => 'Restart Gateway (coliv_app)',
            'command' => null,
            'container' => 'coliv_app',
            'category' => 'Container Management',
            'danger' => 'danger',
            'docker_restart' => true,
        ],
        'restart_whatsapp' => [
            'label' => 'Restart WhatsApp',
            'command' => null,
            'container' => 'coliv_whatsapp',
            'category' => 'Container Management',
            'danger' => 'danger',
            'docker_restart' => true,
        ],
        'restart_redis' => [
            'label' => 'Restart Redis',
            'command' => null,
            'container' => 'coliv_redis',
            'category' => 'Container Management',
            'danger' => 'danger',
            'docker_restart' => true,
        ],
    ];

    public function __construct(protected DockerService $docker) {}

    public function index()
    {
        $grouped = [];
        foreach ($this->commands as $key => $cmd) {
            $grouped[$cmd['category']][$key] = $cmd;
        }

        return view('commands.index', [
            'grouped' => $grouped,
        ]);
    }

    public function run(Request $request): JsonResponse
    {
        $key = $request->input('command');

        if (!isset($this->commands[$key])) {
            return response()->json(['success' => false, 'output' => 'Unknown command.'], 422);
        }

        $cmd = $this->commands[$key];

        // Docker restart commands
        if (!empty($cmd['docker_restart'])) {
            $result = $this->docker->restartContainer($cmd['container']);
            return response()->json([
                'success' => $result['success'],
                'output' => $result['message'],
            ]);
        }

        // Docker exec commands
        $output = $this->docker->exec($cmd['container'], $cmd['command']);

        return response()->json([
            'success' => true,
            'output' => $output ?: '(no output)',
        ]);
    }
}
