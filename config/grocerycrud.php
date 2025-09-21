<?php

return [

    // database config
    'db' => [
        'adapter' => [   // 👈 ye key add karo
            'driver'   => 'Pdo_Mysql',
            'host'     => env('DB_HOST', '127.0.0.1'),
            'database' => env('DB_DATABASE', 'grocery_db'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => 'utf8'
        ]
    ],

    // grocerycrud config
    'config' => [
        'default_per_page' => 10,
        'date_format'      => 'uk-date',
        'default_theme'    => 'bootstrap-v5',
        'assets_url'       => env('APP_URL', '/') . 'grocery-crud/',
    ],

    // license key (zaroori hai enterprise ke liye)
    'license' => env('GROCERYCRUD_LICENSE', ''),
];
