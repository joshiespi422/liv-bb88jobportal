<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\ComplianceFormReturnType;
use App\Enums\ComplianceAgency;

class ComplianceForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency',
        'code',
        'name',
        'return_type',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'agency' => ComplianceAgency::class,
            'return_type' => ComplianceFormReturnType::class,
        ];
    }

    public function companyComplianceForms(): HasMany
    {
        return $this->hasMany(CompanyComplianceForm::class);
    }
}
