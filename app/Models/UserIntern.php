<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIntern extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';
    public $incrementing = false;
 
    protected $fillable = [
        'user_id',
        'department_id',
        'school',
    ];

    // Define a one-to-one relationship between user_interns and users
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Define a many-to-one relationship between user_interns and departments (one department can have many employees)
    public function department() : BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
