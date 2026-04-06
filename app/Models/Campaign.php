<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'name',
        'description',
        'type',
        'config',
        'status',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
