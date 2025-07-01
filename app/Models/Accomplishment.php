<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Accomplishment extends Model
{
    use HasFactory;

    protected $fillable = [
        //
    ];

    // Define a many-to-many relationship with users and tasks (pivot table)
    public function tasks() : BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }

    // Define a one-to-many relationship between accomplishments and users
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
