<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FarmParcel extends Model
{
    use HasFactory;
    protected $table = 'farm_parcels';
    protected $fillable = ['farmer_profile_id', 'brgy', 'city', 'total_farm_area', 'is_whithin_ancentral_domain', 'is_agrarian_reform_beneficiary', 'document', 'ownership_type', 'landowner_name', 'is_other', 'farmer_in_rotation_name', 'uuid'];
}
