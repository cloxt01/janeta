<?php

return [
    'telegram' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
        'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org'),
        'polling_timeout' => (int) env('TELEGRAM_POLLING_TIMEOUT', 30),
    ],
    'alarm_interval' => (int) env('JANETA_ALARM_INTERVAL', 60),
    'alarm_chat_id' => env('JANETA_ALARM_CHAT_ID'),
    'ssh_timeout' => (int) env('OLT_SSH_TIMEOUT', 10),
    'snmp_timeout' => (int) env('OLT_SNMP_TIMEOUT', 3000000),
    'snmp_retries' => (int) env('OLT_SNMP_RETRIES', 2),
    'debug_enabled' => (bool) env('OLT_DEBUG_ENABLED', false),
    'debug_log_channel' => env('OLT_DEBUG_LOG_CHANNEL', 'olt'),
];
