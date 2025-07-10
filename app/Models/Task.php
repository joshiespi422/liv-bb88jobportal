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
        'title',
        'description',
        'collateral',
        'department_id',
        'deadline',
        'priority',
        'user_type_id',
        'status_id'
    ];

    // relationship to user type, many task has one user type
    public function userType() : BelongsTo
    {
        return $this->belongsTo(UserType::class);
    }

    // relationship to status, many task has one status
    public function status() : BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    // relationship to department, many task has one department
    public function department() : BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // relationship to user, many task has many user
    public function users() : BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    // relationship to accomplishment, many task has many accomplishment
    public function accomplishments() : BelongsToMany
    {
        return $this->belongsToMany(Accomplishment::class);
    }
}
