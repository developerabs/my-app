<?php

namespace App\Payments\Resolvers;

use App\Models\landlord\Gateway;

class CredentialResolver
{
    public static function resolve($gateway, $foreceLandlord = false)
    {
        if ($foreceLandlord) {
            $credentials = Gateway::on('sherazipos_landlord')->where('type', 'payment')->where('name', $gateway)->first()->credentials;
            return $credentials;
        }
    }
}