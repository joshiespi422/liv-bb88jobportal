<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salary extends Model
{
    use HasFactory;

    protected $fillable = [
        //
    ];

    // Define a one-to-many relationship between salaries and users
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
