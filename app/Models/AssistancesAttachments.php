<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssistancesAttachments extends Model
{
    use HasFactory;


    protected $table = 'assistance_attachments';
    protected $fillable = ['farmer_id', 'attachment', 'uuid'];
}
