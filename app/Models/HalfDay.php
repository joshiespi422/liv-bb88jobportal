<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HalfDay extends Model
{
    protected $fillable = [
        'requester_id',
        'signer_id',
        'status_id',
        'date',
        'shift',
        'reason',
        'reject_reason',
    ];

    // Define a one-to-many relationship between half_days and users (requester)
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    // Define a one-to-many relationship between half_days and users (signer)
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_id');
    }

    // Define a one-to-many relationship between half_days and statuses
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }
}
