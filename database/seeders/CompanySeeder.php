<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'name' => 'MIGS Masters Institute For Graphics Inc.',
                'tin' => '010-326-819-000',
                'address' => 'Penthouse 1 2F, Plaza Victoria Sto. Rosario Street Santo Domingo Angeles City 2009',
            ],
            [
                'name' => 'BB88 Advertising And Digital Solutions Inc.',
                'tin' => '754-728-161-000',
                'address' => 'Unit D 2/F Plaza Victoria Sto. Rosario St. Santo Domingo Angeles City 2009',
            ],
            [
                'name' => 'Biorganism Corporation',
                'tin' => '600-875-090-00000',
                'address' => '529 Unit D 2/F Plaza Victoria Sto. Rosario Santo Domingo 2009 Angeles City Pampanga Philippines',
            ],
            [
                'name' => 'MHR Phils Construction Corporation',
                'tin' => '606-495-245-00000',
                'address' => '529 2/F Plaza Victoria Sto. Rosario St. Santo Domingo 2009 Angeles City Pampanga Philippines',
            ],
            [
                'name' => 'Regreen Life Inc.',
                'tin' => '600-874-545-00000',
                'address' => '529 Unit D 2/F Plaza Victoria Rosario St. Santo Domingo 2009 Angeles City Pampanga Philippines',
            ],
        ];

        foreach ($companies as $company) {
            Company::create($company);
        }
    }
}
