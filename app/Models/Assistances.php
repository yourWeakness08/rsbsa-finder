<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assistances extends Model
{
    use HasFactory;

    protected $table = 'assistances';
    protected $fillable = ['farmer_id', 'livelihood', 'assistance_id', 'reference_no', 'status', 'livelihood', 'amount', 'purpose', 'remarks', 'created_by', 
    'approved_by', 'approved_remarks', 'approved_at', 'disapproved_by', 'disapproved_remarks', 'disapproved_at', 'cancelled_by', 'cancelled_remarks', 'cancelled_at', 
    'is_archived', 'archived_by', 'archived_at', 'uuid'];

    public function assistance(){
        return $this->belongsTo(Assistance::class, 'assistance_id', 'id');
    }
}
