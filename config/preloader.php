<?php

return [
    'enabled' => env('APP_PRELOADER_ENABLED', true),
    'duration_ms' => (int) env('APP_PRELOADER_DURATION_MS', 4600),
];
