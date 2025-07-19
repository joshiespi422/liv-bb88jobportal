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
}
