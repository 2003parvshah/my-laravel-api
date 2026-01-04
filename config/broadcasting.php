<?php

return [

    'default' => env('BROADCAST_DRIVER', 'null'),

    'guards' => ['sanctum'],

    'connections' => [

      'pusher' => [
    'driver' => 'pusher',
    'key' => env('PUSHER_APP_KEY'),
    'secret' => env('PUSHER_APP_SECRET'),
    'app_id' => env('PUSHER_APP_ID'),
    'options' => [
        'cluster' => env('PUSHER_APP_CLUSTER'),
        // Only use the host if it's actually set in .env
        'host' => env('PUSHER_HOST') ?: "api-".env('PUSHER_APP_CLUSTER', 'mt1').".pusher.com",
        'port' => env('PUSHER_PORT', 443),
        'scheme' => env('PUSHER_SCHEME', 'https'),
        'encrypted' => true,
        'useTLS' => env('PUSHER_APP_USETLS', true),
    ],
],
        //log : ture for debugging
        // generate like bleow
        // [2025-07-01 12:32:42] local.DEBUG: trigger POST: {"name":"follow-request","data":"{\"sender\":{\"id\":2,\"name\":\"parv shah\",\"email\":\"2003parv@gmail.com\",\"google_id\":null,\"avatar\":null,\"status\":\"offline\",\"last_seen_at\":null,\"email_verified_at\":null,\"created_at\":\"2025-06-30T09:29:10.000000Z\",\"updated_at\":\"2025-06-30T10:59:44.000000Z\"},\"receiverId\":1}","channel":"follow"} {"post_value":"{\"name\":\"follow-request\",\"data\":\"{\\\"sender\\\":{\\\"id\\\":2,\\\"name\\\":\\\"parv shah\\\",\\\"email\\\":\\\"2003parv@gmail.com\\\",\\\"google_id\\\":null,\\\"avatar\\\":null,\\\"status\\\":\\\"offline\\\",\\\"last_seen_at\\\":null,\\\"email_verified_at\\\":null,\\\"created_at\\\":\\\"2025-06-30T09:29:10.000000Z\\\",\\\"updated_at\\\":\\\"2025-06-30T10:59:44.000000Z\\\"},\\\"receiverId\\\":1}\",\"channel\":\"follow\"}"} 


        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],

];
