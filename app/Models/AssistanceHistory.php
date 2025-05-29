<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistanceHistory extends Model
{
    protected $table = 'assistance_history';
    protected $fillable = ['farmer_id', 'livelihood', 'assistance_id', 'amount', 'remarks', 'created_by'];
}
