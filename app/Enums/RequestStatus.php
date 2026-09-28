<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Released = 'released';
    case Rejected = 'rejected';
    case CancellationRequested = 'cancellation_requested';
    case Cancelled = 'cancelled';

    /**
     * Get the label shown in the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::CancellationRequested => 'Cancellation requested',
            default => ucfirst($this->value),
        };
    }
}
