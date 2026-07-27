<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyComplianceForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'compliance_form_id',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'compliance_form_id' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function complianceForm(): BelongsTo
    {
        return $this->belongsTo(ComplianceForm::class);
    }

    public function complianceUploads(): HasMany
    {
        return $this->hasMany(ComplianceUpload::class);
    }
}
