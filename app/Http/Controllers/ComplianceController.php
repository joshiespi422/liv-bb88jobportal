<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ComplianceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        // Build the query based on user permissions
        $query = $this->getCompaniesQuery();

        // Execute the query and format the results
        $companies = $this->formatCompanies($query->get());

        // Render the view with the companies and necessary props
        return Inertia::render('ComplianceView', [
            'companies' => $companies,
        ]);
    }

    /**
     * Build the Eloquent query for fetching companies.
     */
    private function getCompaniesQuery(): Builder
    {
        return Company::query()->select('id', 'slug', 'name', 'tin', 'address');
    }

    /**
     * Format the collection of companies for the view.
     */
    private function formatCompanies($companies)
    {
        return $companies->map(function ($company) {
            return [
                'id' => $company->id,
                'slug' => $company->slug,
                'name' => $company->name,
                'tin' => $company->tin,
                'address' => $company->address,
            ];
        });
    }
}
