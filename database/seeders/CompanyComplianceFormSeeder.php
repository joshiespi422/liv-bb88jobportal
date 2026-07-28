<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\ComplianceForm;
use Illuminate\Support\Facades\DB;

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
            $insertData = [];
            foreach ($companies as $company) {
                foreach ($formIds as $formId) {
                    $insertData[] = [
                        'company_id' => $company->id,
                        'compliance_form_id' => $formId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($insertData)) {
                DB::table('company_compliance_forms')->insertOrIgnore($insertData);
            }
        });
    }
}
