<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmActivity extends Model
{
    protected $table = 'crm_activities';
    protected $fillable = ['user_id', 'subject_type', 'subject_id', 'type', 'description', 'metadata'];
    protected function casts(): array { return ['metadata' => 'array']; }
}
