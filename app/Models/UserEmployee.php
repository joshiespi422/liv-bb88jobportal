<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserEmployee extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';
    public $incrementing = false;
 
    protected $fillable = [
        // 
    ];

    // Define a one-to-one relationship between user_employees and users
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Define a many-to-one relationship between user_employees and departments (one department can have many employees)
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

}
