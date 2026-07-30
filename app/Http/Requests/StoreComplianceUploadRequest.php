<?php

namespace App\Http\Requests;

use App\Enums\ComplianceFormReturnType;
use App\Models\CompanyComplianceForm;
use App\Models\ComplianceUpload;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreComplianceUploadRequest extends FormRequest
{
    /**
     * The resolved pivot record connecting the company and form.
     * Cached so don't query it twice (once in rules(), once in withValidator()).
     */
    private ?CompanyComplianceForm $companyComplianceForm = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyComplianceForm = $this->companyComplianceForm();
        $form = $this->route('form');

        return [
            'start_date' => [
                'required',
                'date',
                'after_or_equal:2000-01-01',
                'before_or_equal:' . now()->addYear()->endOfYear()->toDateString(),
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date',
            ],
            'year' => [
                'required',
                'integer',
                'digits:4',
            ],
            'period' => [
                'required',
                'integer',
                $this->periodRangeRule($form->return_type),
                Rule::unique('compliance_uploads', 'period')
                    ->where(fn ($query) => $query
                        ->where('company_compliance_form_id', $companyComplianceForm->id)
                        ->where('year', $this->input('year'))
                    ),
            ],
            'document' => [
                'required',
                'file',
                'mimes:pdf',
                'max:2048',
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'period.unique' => 'A compliance upload for this period and year already exists.',
            'end_date.after' => 'The end date must be after the start date.',
            'document.mimes' => 'The document must be a PDF file.',
            'document.max' => 'The document must not be larger than 2MB.',
        ];
    }

    /**
     * Checks that need cross-field logic or a DB lookup:
     * - the date span is a sane length for the return type
     * - the range doesn't overlap an existing upload for this form
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('start_date') || $validator->errors()->has('year')) {
                return;
            }

            $startDate = Carbon::parse($this->input('start_date'));
            $submittedYear = (int) $this->input('year');

            // The year must match the year start_date falls in — this is what
            // anchors mid-year fiscal periods and Dec→Jan spans consistently,
            // and it must be something the user explicitly confirms, not
            // something we silently correct for them.
            if ($startDate->year !== $submittedYear) {
                $validator->errors()->add(
                    'year',
                    "The year must match the start date's year ({$startDate->year})."
                );
                return;
            }

            if ($validator->errors()->has('end_date')) {
                return;
            }

            $form = $this->route('form');
            $endDate = Carbon::parse($this->input('end_date'));
            $spanDays = $startDate->diffInDays($endDate) + 1;

            [$min, $max] = $this->expectedSpanDays($form->return_type);

            if ($spanDays < $min || $spanDays > $max) {
                $validator->errors()->add(
                    'end_date',
                    "The date range ({$spanDays} days) doesn't match a typical " .
                        strtolower($form->return_type->label()) . ' period.'
                );
                return;
            }

            $overlaps = ComplianceUpload::query()
                ->where('company_compliance_form_id', $this->companyComplianceForm()->id)
                ->where('start_date', '<=', $endDate)
                ->where('end_date', '>=', $startDate)
                ->exists();

            if ($overlaps) {
                $validator->errors()->add(
                    'start_date',
                    'This date range overlaps with an existing upload for this form.'
                );
            }
        });
    }

    private function companyComplianceForm(): CompanyComplianceForm
    {
        if ($this->companyComplianceForm !== null) {
            return $this->companyComplianceForm;
        }

        $company = $this->route('company');
        $form = $this->route('form');

        $companyComplianceForm = CompanyComplianceForm::query()
            ->where('company_id', $company->id)
            ->where('compliance_form_id', $form->id)
            ->first();

        abort_if($companyComplianceForm === null, 404, 'This form is not assigned to this company.');

        return $this->companyComplianceForm = $companyComplianceForm;
    }

    /**
     * Build the min/max rule for `period` based on the form's return type.
     */
    private function periodRangeRule(ComplianceFormReturnType $returnType): string
    {
        return match ($returnType) {
            ComplianceFormReturnType::MONTHLY => 'between:1,12',
            ComplianceFormReturnType::QUARTERLY => 'between:1,4',
            ComplianceFormReturnType::ANNUAL => 'in:1',
            ComplianceFormReturnType::CUSTOM => 'between:1,366',
        };
    }

    /**
     * [min, max] allowed days for the date range, per return type.
     * Buffers account for short/long months, leap years, and inclusive counting.
     */
    private function expectedSpanDays(ComplianceFormReturnType $returnType): array
    {
        return match ($returnType) {
            ComplianceFormReturnType::MONTHLY => [28, 31],
            ComplianceFormReturnType::QUARTERLY => [89, 92],
            ComplianceFormReturnType::ANNUAL => [365, 366],
            ComplianceFormReturnType::CUSTOM => [1, 731], // up to ~2 years, deliberately loose
        };
    }
}