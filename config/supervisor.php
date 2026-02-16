<?php

return [
    'containers' => [
        'coliv_supervisor' => ['label' => 'Supervisor', 'conf_dir' => '/etc/supervisor/conf.d'],
        'coliv_app' => ['label' => 'Gateway (PHP 8)', 'conf_dir' => '/etc/supervisor/conf.d-available'],
        'coliv_beta' => ['label' => 'Beta (PHP 7.4)', 'conf_dir' => '/etc/supervisor/conf.d'],
        'coliv_websocket' => ['label' => 'WebSocket', 'conf_dir' => '/etc/supervisor/conf.d-available'],
    ],
];
