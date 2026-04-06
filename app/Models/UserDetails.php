<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetails extends Model
{
    use HasFactory;

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

    ];
}
