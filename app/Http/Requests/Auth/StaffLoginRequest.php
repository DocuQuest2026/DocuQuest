<?php

namespace App\Http\Requests\Auth;

use App\Enums\Role;

class StaffLoginRequest extends LoginRequest
{
    /**
     * @return array<int, Role>
     */
    protected function allowedRoles(): array
    {
        return Role::officeRoles();
    }
}
