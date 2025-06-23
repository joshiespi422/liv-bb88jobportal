<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        //
    ];

    // relationship to user type, one task has one user type
    public function userType() : BelongsTo
    {
        return $this->belongsTo(UserType::class);
    }

    // relationship to status, one task has one status
    public function status() : BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    // relationship to department, one task has one department
    public function department() : BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // relationship to user, many task has many user
    public function user() : BelongsToMany
    {
        return $this->BelongsToMany(User::class);
    }
}
