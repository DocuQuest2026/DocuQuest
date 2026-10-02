<?php

namespace App\Http\Requests;

use App\Enums\DocumentType;
use App\Enums\EnrolmentStatus;
use App\Enums\ValidIdType;
use App\Rules\GmailAddress;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
     * Store the email in lowercase, since Gmail addresses are not case sensitive, and drop
     * stray spaces around the contact number.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => mb_strtolower(trim($this->email))]);
        }

        if (is_string($this->contact_no)) {
            $this->merge(['contact_no' => trim($this->contact_no)]);
        }
    }

    /**
     * Every chosen document must be one the registrar actually offers.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $chosen = $this->input('documents');

                if (! is_array($chosen)) {
                    return;
                }

                foreach (array_keys($chosen) as $type) {
                    if (DocumentType::tryFrom((string) $type) === null) {
                        $validator->errors()->add('documents', __('Choose only from the documents listed.'));

                        return;
                    }
                }
            },
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'documents.required' => __('Choose at least one document.'),
            'documents.min' => __('Choose at least one document.'),
            'documents.*.copies.between' => __('You can request 1 to 10 copies of each document.'),
            'first_name.not_regex' => __('The first name must not contain numbers.'),
            'middle_name.not_regex' => __('The middle name must not contain numbers.'),
            'last_name.not_regex' => __('The last name must not contain numbers.'),
            'contact_no.digits' => __('The contact number must be exactly 11 digits, numbers only (for example 09171234567).'),
        ];
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
            'first_name' => ['required', 'string', 'max:255', 'not_regex:/\p{N}/u'],
            'middle_name' => ['nullable', 'string', 'max:255', 'not_regex:/\p{N}/u'],
            'last_name' => ['required', 'string', 'max:255', 'not_regex:/\p{N}/u'],
            'course' => ['required', 'string', Rule::in(config('school.courses'))],
            'enrolment_status' => ['required', Rule::enum(EnrolmentStatus::class)],
            'email' => ['required', 'string', 'email', 'max:255', new GmailAddress],
            'contact_no' => ['required', 'string', 'digits:11'],
            'documents' => ['required', 'array', 'min:1', 'max:'.count(DocumentType::cases())],
            'documents.*.copies' => ['required', 'integer', 'between:1,10'],
            'purpose' => ['required', 'string', 'max:1000'],
            'designated_representative_name' => ['nullable', 'string', 'max:255'],
            'designated_representative_id_type' => ['nullable', 'required_with:designated_representative_name', Rule::enum(ValidIdType::class)],
        ];
    }
}
