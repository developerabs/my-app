<?php

namespace App\Contracts;

interface RestorableConflictInterface
{
    /**
     * English: Define the logic to check for restoration conflicts.
     */
    public function hasRestorationConflict(): bool;
}