<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['price', 'tierStatus','custId','orderId','orderStatus','description','created_at'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'orderId', 'orderId');
    }
}
