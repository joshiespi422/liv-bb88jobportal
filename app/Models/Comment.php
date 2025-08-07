<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'commentable_id',
        'commentable_type',
        'message',
    ];

    /**
     * Get the parent commentable model (project, task, accomplishment, ...).
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    // Define a one-to-many relationship between comments and users
    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Define a one-to-many polymorphic relationship between comments and notifications
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
