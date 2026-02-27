<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assistances extends Model
{
    use HasFactory;

    protected $table = 'assistances';
    protected $fillable = ['farmer_id', 'livelihood', 'assistance_id', 'reference_no', 'livelihood', 'amount', 'purpose', 'remarks', 'created_by', 'uuid'];
}
