<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        //
    ];

    // Define a one-to-many relationship between departments and user_employees (one department can have many employees)
    public function employeeDetails() : HasMany
    {
        return $this->hasMany(UserEmployee::class);
    }

    // Define a one-to-many relationship between departments and user_interns (one department can have many interns)
    public function internDetails() : HasMany
    {
        return $this->hasMany(UserIntern::class);
    }

    // Define a one-to-many relationship between departments and tasks (one department can have many tasks)
    public function tasks() : HasMany
    {
        return $this->hasMany(Task::class);
    }
}
