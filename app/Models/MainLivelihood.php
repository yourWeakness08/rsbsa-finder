<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainLivelihood extends Model
{
    use HasFactory;
    protected $table = 'main_livelihood';
    protected $fillable = ['farm_profile_id', 'main_livelihood', 'meta', 'value', 'uuid'];
}
