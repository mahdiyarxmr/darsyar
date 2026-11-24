<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'enabled' => Env::bool('SKYROOM_ENABLED', false),
    'api_url' => rtrim((string) Env::get('SKYROOM_API_URL', 'https://www.skyroom.online/skyroom/api/'), '/'),
    'api_key' => Env::get('SKYROOM_API_KEY', ''),
    'timeout' => 15,
];
