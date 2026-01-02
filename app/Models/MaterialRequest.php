<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MaterialRequest extends Model
{
    protected $fillable = [
        'name',
        'quantity',
        'requested_by',
        'signed_by',
        'department_id',
        'status_id',
        'purpose',
        'description',
        'amount',
        'remarks',
        'date_needed',
        'reject_reason',
    ];

    // Define a one-to-many relationship between material_requests and users (requester)
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    // Define a one-to-many relationship between material_requests and users
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    // Define a one-to-many relationship between material_requests and departments
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Define a one-to-many relationship between material_requests and statuses
    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    // Define a one-to-many polymorphic relationship between material_requests and notifications
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}

