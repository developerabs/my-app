<?php

use Illuminate\Support\Facades\Config;

if (!function_exists('getLandlordMailSettings')) {
    function getLandlordMailSettings()
    {
        return \App\Models\landlord\LandlordSetting::where('group', 'email')
            ->pluck('value', 'key')
            ->toArray();
    }
}

if(!function_exists('setMailConfig')) {
    function setMailConfig($settings) {
        if(!$settings) return;

        Config::set('mail.default', 'smtp');

        Config::set('mail.mailers.smtp.host', $settings['mail_host']);
        Config::set('mail.mailers.smtp.port', $settings['mail_port']);
        Config::set('mail.mailers.smtp.username', $settings['mail_username']);
        Config::set('mail.mailers.smtp.password', $settings['mail_password']);
        Config::set('mail.mailers.smtp.encryption', $settings['mail_encryption'] ?: null);

        Config::set('mail.from.address', $settings['mail_from_address']);
        Config::set('mail.from.name', $settings['mail_from_name'] ?? 'Sherazi POS');
    }
}