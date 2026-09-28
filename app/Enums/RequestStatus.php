<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Pending = 'pending';
    case Cancelled = 'cancelled';

    /**
     * Get the label shown in the interface.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
