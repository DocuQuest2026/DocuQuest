<?php

namespace App\Enums;

enum Role: string
{
    case Student = 'student';
    case Staff = 'staff';
    case Admin = 'admin';

    /**
     * Roles that belong to the registrar's office and are created by an administrator.
     *
     * @return array<int, self>
     */
    public static function officeRoles(): array
    {
        return [self::Staff, self::Admin];
    }

    /**
     * Get the label shown in the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Staff => 'Registrar Staff',
            self::Admin => 'Registrar Administrator',
        };
    }
}
