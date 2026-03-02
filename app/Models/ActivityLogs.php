<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLogs extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'table',
        'message',
        'action',
        'status',
        'ip',
        'uuid'
    ];

    protected $casts = [
        'properties' => 'array',
    ];
}
