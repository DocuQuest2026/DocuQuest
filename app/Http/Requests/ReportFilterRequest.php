<?php

namespace App\Http\Requests;

use App\Enums\RequestStatus;
use App\Models\RecordRequest;
use App\Services\RequestReport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', RecordRequest::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.$this->firstDate()],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from', 'after_or_equal:'.$this->firstDate()],
            'status' => ['nullable', Rule::enum(RequestStatus::class)],
            'document_type_id' => ['nullable', 'integer', Rule::exists('document_types', 'id')],
        ];
    }

    /**
     * The earliest date the report can cover: the first day of the month the system started.
     */
    public function firstDate(): string
    {
        return Carbon::createFromFormat('!Y-m', config('school.first_request_month'))->toDateString();
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $first = Carbon::parse($this->firstDate())->format('F j, Y');

        return [
            'from.after_or_equal' => __('The system started on :date, so reports can not start earlier.', ['date' => $first]),
            'to.after_or_equal' => __('The end date must be on or after the start date, and not before :date.', ['date' => $first]),
        ];
    }

    /**
     * The report for the chosen filters. With no dates it covers the current month so far.
     */
    public function report(): RequestReport
    {
        return new RequestReport(
            from: $this->filled('from') ? Carbon::createFromFormat('!Y-m-d', $this->validated('from')) : now()->startOfMonth(),
            to: $this->filled('to') ? Carbon::createFromFormat('!Y-m-d', $this->validated('to')) : now(),
            status: $this->filled('status') ? RequestStatus::from($this->validated('status')) : null,
            documentTypeId: $this->filled('document_type_id') ? (int) $this->validated('document_type_id') : null,
        );
    }
}
