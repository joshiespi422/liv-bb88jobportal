<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\ComplianceForm;

class CompanyComplianceFormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $formIds = ComplianceForm::pluck('id');
        if ($formIds->isEmpty()) {
            return;
        }

        Company::chunk(100, function ($companies) use ($formIds) {
            foreach ($companies as $company) {
                $company->complianceForms()->sync($formIds);
            }
        });
    }
}
