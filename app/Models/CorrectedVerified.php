<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectedVerified extends Model
{
    use HasFactory;
    protected $table = 'corrected_and_verified';
    protected $fillable = ['farmer_id', 'paper_date', 'official', 'muni_city_official', 'cafc_chairman', 'uuid'];
}
