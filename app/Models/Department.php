<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        //
    ];

    // Define a one-to-many relationship between departments and user_employees (one department can have many employees)
    public function employeeDetails()
    {
        return $this->hasMany(UserEmployee::class);
    }
}
