<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    use HasFactory;

    protected $fillable = [
        //
    ];

    // relationship to tasks, one status can have many tasks
    public function tasks() : HasMany
    {
        return $this->hasMany(Task::class);
    }

    // relationship to leaves, one status can have many leaves
    public function leaves() : HasMany
    {
        return $this->hasMany(Leave::class);
    }

    // relationship to project_issues, one status can have many project_issues
    public function projectIssues() : HasMany
    {
        return $this->hasMany(ProjectIssue::class);
    }

    // relationship to users, one status can have many users
    public function users() : HasMany
    {
        return $this->hasMany(User::class);
    }

    // relationship to material_requests, one status can have many material_requests
    public function materialRequests() : HasMany
    {
        return $this->hasMany(MaterialRequest::class);
    }

    // relationship to salaries, one status can have many salaries
    public function salaries() : HasMany
    {
        return $this->hasMany(Salary::class);
    }

    // relationship to overtimes, one status can have many overtimes
    public function overtimes() : HasMany
    {
        return $this->hasMany(Overtime::class);
    }

    // relationship to half_days, one status can have many half_days
    public function halfDays() : HasMany
    {
        return $this->hasMany(HalfDay::class);
    }
}
