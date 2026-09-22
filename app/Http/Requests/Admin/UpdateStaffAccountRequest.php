<?php

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStaffAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->route('user')),
            ],
            'role' => ['required', Rule::enum(Role::class)->only(Role::officeRoles())],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Stop an administrator from locking themselves out of the system.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->route('user')->is($this->user()) || $validator->errors()->isNotEmpty()) {
                    return;
                }

                if ($this->input('role') !== Role::Admin->value) {
                    $validator->errors()->add('role', __('You cannot change your own role.'));
                }

                if (! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', __('You cannot deactivate your own account.'));
                }
            },
        ];
    }
}
