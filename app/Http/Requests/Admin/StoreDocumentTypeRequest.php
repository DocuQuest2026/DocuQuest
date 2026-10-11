<?php

namespace App\Http\Requests\Admin;

use App\Models\DocumentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', DocumentType::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(DocumentType::class, 'name')],
            'fee' => ['required', 'integer', 'min:0', 'max:100000'],
            'processing_days' => ['required', 'integer', 'min:1', 'max:60'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
