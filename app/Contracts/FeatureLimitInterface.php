<?php

namespace App\Contracts;

interface FeatureLimitInterface
{
    public function getFeatureLimitKey(): string;
}