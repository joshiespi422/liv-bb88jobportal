<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Company;
use App\Models\ComplianceForm;
use App\Models\ComplianceUpload;
use App\Models\CompanyComplianceForm;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\StoreComplianceUploadRequest;
use Illuminate\Support\Str;

class ComplianceUploadController extends Controller
{
    public function index(Request $request, Company $company, ComplianceForm $form): Response
    {
        $companyComplianceForm = $this->resolveCompanyComplianceForm($company, $form);

        $uploads = ComplianceUpload::query()
            ->where('company_compliance_form_id', $companyComplianceForm->id)
            ->orderByDesc('year')
            ->orderByDesc('period')
            ->get()
            ->map(fn (ComplianceUpload $upload) => [
                'id' => $upload->id,
                'form_code' => $form->code,
                'year' => $upload->year,
                'period' => $upload->period,
                'start_date' => $upload->start_date->format('Y-m-d'),
                'end_date' => $upload->end_date->format('Y-m-d'),
                'remarks' => $upload->remarks,
                'document_url' => route('compliance.uploads.show', [
                    'company' => $company->slug,
                    'form' => $form->code,
                    'upload' => $upload->slug,
                ]),
            ])
            ->values();

        return Inertia::render('ComplianceUploadView', [
            'company' => $company->only('id', 'slug', 'name', 'tin', 'address'),
            'complianceForm' => [
                'id' => $form->id,
                'code' => $form->code,
                'name' => $form->name,
                'return_type' => $form->return_type->value,
                'return_type_label' => $form->return_type->label(),
                'description' => $form->description,
            ],
            'complianceUploads' => $uploads,
        ]);
    }

    /**
     * Stream the upload's PDF inline so it opens in a new browser tab.
     */
    public function show(Request $request, Company $company, ComplianceForm $form, ComplianceUpload $upload): StreamedResponse
    {
        $companyComplianceForm = $this->resolveCompanyComplianceForm($company, $form);

        abort_unless(
            $upload->company_compliance_form_id === $companyComplianceForm->id,
            404,
            'Upload does not belong to this company/form.'
        );

        abort_unless(Storage::disk('local')->exists($upload->document), 404, 'File not found.');

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk->response($upload->document, basename($upload->document), [
            'Content-Disposition' => 'inline; filename="' . basename($upload->document) . '"',
        ]);
    }

    /**
     * Verify the compliance form is actually connected to the company via the pivot,
     * and return that pivot record. Aborts with 404 if not connected.
     */
    private function resolveCompanyComplianceForm(Company $company, ComplianceForm $form): CompanyComplianceForm
    {
        $companyComplianceForm = CompanyComplianceForm::query()
            ->where('company_id', $company->id)
            ->where('compliance_form_id', $form->id)
            ->first();

        abort_if($companyComplianceForm === null, 404, 'This form is not assigned to this company.');

        return $companyComplianceForm;
    }

    public function store(StoreComplianceUploadRequest $request, Company $company, ComplianceForm $form): RedirectResponse
    {
        $companyComplianceForm = $this->resolveCompanyComplianceForm($company, $form);

        $validated = $request->validated();

        $path = $request->file('document')->store('compliance-uploads', 'local');

        ComplianceUpload::create([
            'company_compliance_form_id' => $companyComplianceForm->id,
            'slug' => (string) Str::ulid(),
            'year' => $validated['year'],
            'period' => $validated['period'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'document' => $path,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return back()->with('success', 'Compliance upload created successfully!');
    }
}