<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateAccountPasswordRequest extends FormRequest
{
    /**
     * The named error bag, so this form's errors don't collide with another form on the same page.
     *
     * @var string
     */
    protected $errorBag = 'resetPassword';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('resetPassword', $this->route('user')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'password' => ['required', 'confirmed', Password::defaults()],
        ];

        if ($this->isChangingOwnPassword()) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        return $rules;
    }

    /**
     * Whether the signed-in user is changing their own password, in which case they must prove
     * they know the current one. Resetting someone else's password does not need theirs.
     */
    public function isChangingOwnPassword(): bool
    {
        return $this->route('user')?->is($this->user()) ?? false;
    }
}
