<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        //
    ];

    // Define a one-to-many relationship between leave_types and leaves
    public function leaves() : HasMany
    {
        return $this->hasMany(Leave::class);
    }

    // Define a one-to-many relationship between leave_types and leave_categories
    public function leaveCategories() : HasMany
    {
        return $this->hasMany(LeaveCategory::class);
    }
}
