<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OthersFarmerInformation extends Model
{
    use HasFactory;
    protected $table = 'others_farmer_information';
    protected $fillable = ['farmer_id', 'mothers_maiden_name', 'is_household_head', 'name_if_not_head', 'is_not_head_relationship', 'no_of_living_members', 'no_of_male', 'no_of_female', 'highest_formal_education', 'is_pwd', 'is_4ps', 'is_ig_mem', 'is_mem_specify', 'has_gov_id', 'id_type', 'id_no', 'is_farmer_coop_mem', 'is_farmer_mem', 'contact_emergency', 'contact_no', 'uuid'];
}
