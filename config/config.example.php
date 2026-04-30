<?php
/**
 * Copy this file to config/config.php and fill in real values.
 * config/config.php is gitignored.
 */
return [
    'app' => [
        'name'   => 'FlexiComp',
        'url'    => 'https://gamespool.example.com', // no trailing slash
        'env'    => 'production', // 'production' or 'development'
        'secret' => 'CHANGE_ME_TO_A_LONG_RANDOM_STRING', // used for signing
        'timezone' => 'Europe/Amsterdam',
    ],
    'db' => [
        'driver'   => 'mysql',
        'host'     => 'localhost',
        'port'     => 3306,
        'database' => 'gamespool',
        'username' => 'gamespool',
        'password' => 'CHANGE_ME',
        'charset'  => 'utf8mb4',
    ],
    'uploads' => [
        // Sub-folders below public/uploads/
        'avatars_dir' => 'avatars',
        'logos_dir'   => 'logos',
        'max_bytes'   => 10 * 1024 * 1024, // 10 MB (image is server-cropped + re-encoded to ~50KB)
    ],
];
