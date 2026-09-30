<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * How recently the user must have been seen to be considered online.
     */
    private const ONLINE_WITHIN_MINUTES = 5;

    /**
     * Mirrors the database defaults so an unsaved model already has a role.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'student',
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function isStudent(): bool
    {
        return $this->role === Role::Student;
    }

    public function isStaff(): bool
    {
        return $this->role === Role::Staff;
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /**
     * Whether the user works in the registrar's office, as staff or administrator.
     */
    public function isOfficeUser(): bool
    {
        return $this->isStaff() || $this->isAdmin();
    }

    /**
     * Whether the user has been seen recently enough to be considered currently online.
     * Session-driven presence (e.g. Redis/database sessions) isn't available here, since this
     * app uses file sessions, so presence is approximated from request activity instead.
     */
    public function isOnline(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->isAfter(now()->subMinutes(self::ONLINE_WITHIN_MINUTES));
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    #[Scope]
    protected function office(Builder $query): Builder
    {
        return $query->whereIn('role', array_column(Role::officeRoles(), 'value'));
    }

    /**
     * @return HasOne<StudentProfile, $this>
     */
    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }
}
