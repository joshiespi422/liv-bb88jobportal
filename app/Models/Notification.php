<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        //
    ];

    /**
     * Get the parent notifiable model (project, task, accomplishment, ...).
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    // Define a one-to-many relationship between notifications and users
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
