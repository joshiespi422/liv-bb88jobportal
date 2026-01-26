<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Overtime extends Model
{
    protected $fillable = [
        'requester_id',
        'signer_id',
        'status_id',
        'date',
        'start_time',
        'end_time',
        'reason',
        'total_hours',
        'reject_reason',
    ];

    // Define a one-to-many relationship between overtimes and users (requester)
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    // Define a one-to-many relationship between overtimes and users (signer)
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_id');
    }

    // Define a one-to-many relationship between overtimes and statuses
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    // Define a one-to-many polymorphic relationship between overtimes and notifications
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
