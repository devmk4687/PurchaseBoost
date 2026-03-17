<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'orderId',
        'custId',
        'credit',
        'debit',
        'description',
        'meta_data'
    ];
}
