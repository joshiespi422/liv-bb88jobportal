<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Enums\ComplianceAgency;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceFormController extends Controller
{
    public function index(Request $request, Company $company): Response
    {
        $activeTab = $this->resolveActiveTab($request);

        $agencies = array_map(fn ($agency) => [
            'id' => $agency->value,
            'label' => $agency->label(),
        ], ComplianceAgency::cases());

        $complianceForms = $this->getComplianceForms($company, $activeTab);

        return Inertia::render('ComplianceFormView', [
            'company' => $company->only('id', 'slug', 'name', 'tin', 'address'),
            'activeTab' => $activeTab,
            'complianceTabs' => $agencies,
            'complianceForms' => $complianceForms,
        ]);
    }

    /**
     * Resolve and validate the active tab against the ComplianceAgency enum.
     */
    private function resolveActiveTab(Request $request): string
    {
        $default = ComplianceAgency::BIR->value;
        $tab = $request->query('tab', $default);

        $valid = array_column(ComplianceAgency::cases(), 'value');

        return in_array($tab, $valid, true) ? $tab : $default;
    }

    /**
     * Get the compliance forms connected to the company, filtered by agency.
     */
    private function getComplianceForms(Company $company, string $agency)
    {
        return $company->companyComplianceForms()
            ->whereHas('complianceForm', function ($query) use ($agency) {
                $query->where('agency', $agency);
            })
            ->with(['complianceForm:id,agency,code,name,return_type,description'])
            ->withCount('complianceUploads')
            ->get()
            ->map(fn ($ccf) => [
                'id' => $ccf->id,
                'compliance_form_id' => $ccf->complianceForm->id,
                'code' => $ccf->complianceForm->code,
                'name' => $ccf->complianceForm->name,
                'return_type' => $ccf->complianceForm->return_type->value,
                'return_type_label' => $ccf->complianceForm->return_type->label(),
                'description' => $ccf->complianceForm->description,
                'uploads_count' => $ccf->compliance_uploads_count,
            ])
            ->values();
    }
}
