<?php

return [
    'fotos' => [
        'driver' => env('ORDENS_SERVICO_FOTOS_DRIVER', 'local'),
        'rclone_bin' => env('ORDENS_SERVICO_FOTOS_RCLONE_BIN', '/usr/bin/rclone'),
        'rclone_config' => env('ORDENS_SERVICO_FOTOS_RCLONE_CONFIG', '/etc/conectta/rclone.conf'),
        'rclone_remote' => env('ORDENS_SERVICO_FOTOS_RCLONE_REMOTE', 'gdrive'),
        'rclone_base_path' => env('ORDENS_SERVICO_FOTOS_RCLONE_PATH', 'Conectta/ordens-servico'),
        'timeout' => (int) env('ORDENS_SERVICO_FOTOS_TIMEOUT', 60),
    ],
];
