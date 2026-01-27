<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Salary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status_id',
        'salary_period_id',
        'rate_day',
        'rate_month',
        'absent_day',
        'absent_deduction',
        'overtime_hour',
        'overtime_amount',
        'gross_pay',
        'net_pay',
    ];

    // Define a one-to-many relationship between salaries and users
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Define a one-to-many relationship between salaries and users (approver)
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // Define a one-to-many relationship between salaries and salary_periods
    public function salaryPeriod(): BelongsTo
    {
        return $this->belongsTo(SalaryPeriod::class);
    }

    // Define a one-to-many relationship between salaries and statuses
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    // Define a many-to-many relationship between salaries and holidays
    public function holidays(): BelongsToMany
    {
        return $this->belongsToMany(Holiday::class);
    }
}
