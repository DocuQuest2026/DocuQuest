<?php

namespace App\Http\Requests\Concerns;

use App\Enums\EnrolmentStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ValidatesStudentProfile
{
    /**
     * Rules for the student-editable profile fields. The student number is excluded because it
     * identifies the student to the registrar and is only set at registration.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function studentProfileRules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'course' => ['required', 'string', 'max:150'],
            'enrolment_status' => ['required', Rule::enum(EnrolmentStatus::class)],
            'year_level' => ['exclude_unless:enrolment_status,'.EnrolmentStatus::Enrolled->value, 'required', 'integer', 'between:1,6'],
            'contact_no' => ['required', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
        ];
    }
}
