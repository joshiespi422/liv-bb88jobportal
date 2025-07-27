<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Accomplishment extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'link',
        'attachment',
        'user_id',
    ];

    // Define a many-to-many relationship with users and tasks (pivot table)
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }

    // Define a one-to-many relationship between accomplishments and users
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Define a one-to-many polymorphic relationship between accomplishments and comments
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // Define a one-to-many polymorphic relationship between accomplishments and notifications
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
