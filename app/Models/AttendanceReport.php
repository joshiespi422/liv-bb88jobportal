<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AttendanceReport extends Model
{
    protected $fillable = [
        'user_id',
        'salary_period_id',
        'day',
        'overtime',
        'absent',
        'halfday',
        'lates',
        'total',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function salaryPeriod(): BelongsTo
    {
        return $this->belongsTo(SalaryPeriod::class);
    }

    public function holidays(): BelongsToMany
    {
        return $this->belongsToMany(Holiday::class);
    }
}
