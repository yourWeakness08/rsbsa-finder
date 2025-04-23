<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FarmingType extends Model
{
    use HasFactory;
    protected $fillable = ['type', 'name', 'created_by', 'updated_by', 'is_archived', 'archived_by', 'uuid'];
}
