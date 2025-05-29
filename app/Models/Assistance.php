<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assistance extends Model
{
    protected $table = 'assistance';
    protected $fillable = ['livelihoods', 'name', 'created_by', 'updated_by', 'is_archived', 'archived_by', 'uuid'];
}
