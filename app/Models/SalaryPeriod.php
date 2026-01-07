<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        //
    ];

    // relationship to salaries, one salary_period can have many salaries
    public function salaries() : HasMany
    {
        return $this->hasMany(Salary::class);
    }
}
