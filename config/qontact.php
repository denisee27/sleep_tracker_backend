<?php
return [
    'enabled' => (bool)env('QONTACT_ENABLED', false),
    'username' => env('QONTACT_USERNAME'),
    'password' => env('QONTACT_PASSWORD'),
    'client_id' => env('QONTACT_CLIENT_ID'),
    'client_secret' => env('QONTACT_CLIENT_SECRET'),
    'channel_id' => env('QONTACT_CHANNEL_ID'),
    'template_id' => env('QONTACT_TEMPLATE_ID')
];
