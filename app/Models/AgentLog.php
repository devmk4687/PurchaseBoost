<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal',
        'plan',
        'result',
    ];

    protected $casts = [
        'plan' => 'array',
        'result' => 'array',
    ];
}
