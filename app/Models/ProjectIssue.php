<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class ProjectIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        //
    ];

    // Define a one-to-many relationship between project_issues and projects
    public function project() : BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Define a one-to-many relationship between project_issues and users
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Define a one-to-many relationship between project_issues and statuses
    public function status() : BelongsTo
    {
        return $this->belongsTo(Status::class);
    }
}
