<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachments extends Model
{
    use HasFactory;
    protected $table = 'farmer_attachments';
    protected $fillable = ['farmer_id', 'filename', 'filepath', 'uuid'];
}
