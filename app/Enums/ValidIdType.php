<?php

namespace App\Enums;

enum ValidIdType: string
{
    case SchoolId = 'school_id';
    case GovernmentId = 'government_id';

    /**
     * Get the label shown in the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::SchoolId => 'School ID',
            self::GovernmentId => 'Government-issued ID (e.g. driver\'s license, passport, UMID)',
        };
    }
}
