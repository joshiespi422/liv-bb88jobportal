<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialRequest extends Model
{
    protected $fillable = [
        'requested_by',
        'signed_by',
        'department_id',
        'status_id',
        'purpose',
        'description',
        'amount',
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
}

