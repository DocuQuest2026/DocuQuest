<?php

namespace App\Enums;

enum EnrolmentStatus: string
{
    case Enrolled = 'enrolled';
    case Graduated = 'graduated';
    case Alumni = 'alumni';

    /**
     * Get the label shown in the interface.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
