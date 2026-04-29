<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'filters',
    ];

    protected $casts = [
        'filters' => 'array',
    ];

    public function members()
    {
        return $this->belongsToMany(LoyaltyMember::class, 'customer_segment_members', 'customer_segment_id', 'customer_detail_id');
    }
}
