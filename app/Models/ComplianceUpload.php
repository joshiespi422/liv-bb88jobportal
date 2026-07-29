<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_compliance_form_id',
        'year',
        'period',
        'start_date',
        'end_date',
        'document',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'company_compliance_form_id' => 'integer',
            'year' => 'integer',
            'period' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function companyComplianceForm(): BelongsTo
    {
        return $this->belongsTo(CompanyComplianceForm::class);
    }
}
