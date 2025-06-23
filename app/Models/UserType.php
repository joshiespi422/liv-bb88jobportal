<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserType extends Model
{
    use HasFactory;

    // protected $fillable = ['type_name'];

    // relationship to users, one user type can have many users
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // relationship to tasks, one user type can have many tasks
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
