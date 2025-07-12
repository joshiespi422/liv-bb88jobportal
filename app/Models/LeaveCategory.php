<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        //
    ];

    // Define a one-to-many relationship between leave_categories and leave_types
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    // Define a one-to-many relationship between leave_categories and leaves
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }
}
