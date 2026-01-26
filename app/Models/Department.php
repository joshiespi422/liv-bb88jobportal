<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Department extends Model
{
    use HasFactory;
    
    public $timestamps = false;

    protected $fillable = [
        'dept_name',
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

    // Define a many-to-many relationship with departments and projects (pivot table)
    public function projects() : BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    // Define a one-to-many relationship between departments and material_requests
    public function materialRequests() : HasMany
    {
        return $this->hasMany(MaterialRequest::class);
    }
}
