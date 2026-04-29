<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyMember extends Model
{
    use HasFactory;

    protected $table = 'customer_details';

    protected $fillable = [
        'customerId',
        'firstName',
        'lastName',
        'company',
        'city',
        'country',
        'phone1',
        'phone2',
        'email',
        'subscriptionDate',
        'website',
    ];
}
