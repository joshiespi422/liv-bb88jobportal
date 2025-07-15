<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    use HasFactory;
    
    protected $fillable = [
        //
    ];

    // Define a many-to-many relationship with projects and tasks (pivot table)
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }
    
    // Define a many-to-many relationship with projects and departments (pivot table)
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }
}
