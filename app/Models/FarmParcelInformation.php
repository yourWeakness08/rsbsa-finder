<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FarmParcelInformation extends Model
{
    use HasFactory;
    protected $table = 'farm_parcel_informations';
    protected $fillable = ['farm_parcels_id', 'farming_type', 'size', 'no_of_head', 'farm_type', 'is_organic_practitioner', 'remarks', 'uuid'];
}
