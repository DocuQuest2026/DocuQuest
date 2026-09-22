<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesStudentProfile;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    use ValidatesStudentProfile;

    /**
     * Get the validation rules that apply to the request.
     *
     * Students are identified by their profile names; office users have a single name field.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...($this->user()->isStudent()
                ? $this->studentProfileRules()
                : ['name' => ['required', 'string', 'max:255']]),
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
