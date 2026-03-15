<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Holiday extends Model
{
    protected $fillable = [
        'name',
        'date',
        'type',
    ];

    // relationship to salaries, many holiday has many salary
    public function salaries(): BelongsToMany
    {
        return $this->belongsToMany(Salary::class);
    }

    public function attendanceReports(): BelongsToMany
    {
        return $this->belongsToMany(AttendanceReport::class);
    }
}
