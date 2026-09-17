<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessagingSubmission extends Model
{
    protected $fillable = ['request_uuid', 'actor_id', 'payload_hash', 'status', 'campaign_id'];
}
