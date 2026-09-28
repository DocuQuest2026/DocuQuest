<?php

namespace App\Http\Requests;

use App\Enums\DocumentType;
use App\Enums\EnrolmentStatus;
use App\Rules\GmailAddress;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecordRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Store the email in lowercase, since Gmail addresses are not case sensitive.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => mb_strtolower(trim($this->email))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_no' => ['required', 'string', 'max:30'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'course' => ['required', 'string', Rule::in(config('school.courses'))],
            'enrolment_status' => ['required', Rule::enum(EnrolmentStatus::class)],
            'email' => ['required', 'string', 'email', 'max:255', new GmailAddress],
            'contact_no' => ['required', 'string', 'max:30'],
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'copies' => ['required', 'integer', 'between:1,10'],
            'purpose' => ['required', 'string', 'max:1000'],
        ];
    }
}
