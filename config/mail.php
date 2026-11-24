<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'mail_enabled' => Env::bool('MAIL_ENABLED', false),
    'from_address' => Env::get('MAIL_FROM_ADDRESS', 'noreply@darsyar.school'),
    'from_name' => Env::get('MAIL_FROM_NAME', 'DarsYar School'),
    'sms_enabled' => Env::bool('SMS_ENABLED', false),
    'sms_provider' => Env::get('SMS_PROVIDER', 'none'),
    'sms_api_key' => Env::get('SMS_API_KEY', ''),
];
