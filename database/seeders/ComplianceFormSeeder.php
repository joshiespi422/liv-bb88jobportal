<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Enums\ComplianceAgency;
use App\Enums\ComplianceFormReturnType;
use App\Models\ComplianceForm;

class ComplianceFormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $forms = [
            [
                'agency' => ComplianceAgency::BIR->value,
                'code' => '0619-E',
                'name' => 'Monthly Remittance Form of Creditable Income Taxes Withheld (Expanded)',
                'return_type' => ComplianceFormReturnType::MONTHLY->value,
            ],
            [
                'agency' => ComplianceAgency::BIR->value,
                'code' => '1601-C',
                'name' => 'Monthly Remittance Return of Income Taxes Withheld on Compensation',
                'return_type' => ComplianceFormReturnType::MONTHLY->value,
            ],
            [
                'agency' => ComplianceAgency::BIR->value,
                'code' => '1601-EQ',
                'name' => 'Quarterly Remittance Return of Creditable Income Taxes Withheld (Expanded)',
                'return_type' => ComplianceFormReturnType::QUARTERLY->value,
            ],
            [
                'agency' => ComplianceAgency::BIR->value,
                'code' => '1604-E',
                'name' => 'Annual Information Return of Creditable Income Taxes Withheld (Expanded)/ Income Payments Exempt from Withholding Tax',
                'return_type' => ComplianceFormReturnType::ANNUAL->value,
            ],
            [
                'agency' => ComplianceAgency::BIR->value,
                'code' => '1702Q',
                'name' => 'Quarterly Income Tax Return For Corporations, Partnerships and Other Non-Individual Taxpayers',
                'return_type' => ComplianceFormReturnType::QUARTERLY->value,
            ],
            [
                'agency' => ComplianceAgency::BIR->value,
                'code' => '1702-RT',
                'name' => 'Annual Income Tax Return Corporation, Partnership and Other Non-Individual Taxpayer Subject Only to REGULAR Income Tax Rate',
                'return_type' => ComplianceFormReturnType::ANNUAL->value,
            ],
            [
                'agency' => ComplianceAgency::BIR->value,
                'code' => '2551Q',
                'name' => 'Quarterly Percentage Tax Return',
                'return_type' => ComplianceFormReturnType::QUARTERLY->value,
            ],
        ];

        foreach ($forms as $form) {
            ComplianceForm::updateOrCreate(
                ['agency' => $form['agency'], 'code' => $form['code']],
                $form
            );
        }
    }
}
