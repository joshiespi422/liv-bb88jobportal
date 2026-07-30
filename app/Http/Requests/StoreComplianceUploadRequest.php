<?php

namespace App\Http\Requests;

use App\Enums\ComplianceFormReturnType;
use App\Models\CompanyComplianceForm;
use App\Models\ComplianceUpload;
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
        $form = $this->route('form'); // ComplianceForm, bound via {form:code}

        return [
            'year' => [
                'required',
                'integer',
                'digits:4',
                'min:2000',
                'max:' . (now()->year + 1),
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
            'start_date' => [
                'required',
                'date',
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
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
            'document.mimes' => 'The document must be a PDF file.',
            'document.max' => 'The document must not be larger than 2MB.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
        ];
    }

    /**
     * Additional checks that can't be expressed as simple rules:
     * - start_date/end_date must fall within the declared year
     * - date range must not overlap with any existing upload for this form
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('start_date') || $validator->errors()->has('end_date')) {
                return; // don't stack more errors on already-invalid dates
            }

            $year = (int) $this->input('year');
            $startDate = $this->date('start_date');
            $endDate = $this->date('end_date');

            if ($startDate && $startDate->year !== $year) {
                $validator->errors()->add('start_date', "The start date must fall within {$year}.");
            }

            if ($endDate && $endDate->year !== $year) {
                $validator->errors()->add('end_date', "The end date must fall within {$year}.");
            }

            if (! $startDate || ! $endDate) {
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

    /**
     * Resolve (and verify) the pivot connecting the company and form,
     * mirroring the controller's own ownership check.
     */
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
}