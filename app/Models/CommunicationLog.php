<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'communication_template_id',
        'customer_detail_id',
        'channel',
        'recipient',
        'subject',
        'message',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function customer()
    {
        return $this->belongsTo(LoyaltyMember::class, 'customer_detail_id');
    }

    public function template()
    {
        return $this->belongsTo(CommunicationTemplate::class, 'communication_template_id');
    }
}
