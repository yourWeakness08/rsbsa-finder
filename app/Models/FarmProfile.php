<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FarmProfile extends Model
{
    use HasFactory;
    protected $table = 'farm_profile';
    protected $fillable = ['farmer_id', 'main_livelihood', 'farming_gross', 'no_farming_gross', 'farm_parcel_no', 'is_arb', 'uuid'];
}
