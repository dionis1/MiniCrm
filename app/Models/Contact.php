<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    use HasFactory;

    protected $table = 'employees';

    protected $fillable = ['user_id', 'companie_id', 'first_name', 'last_name', 'email', 'phone', 'position', 'tags'];

    protected function casts(): array
    {
        return ['tags' => 'array'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'companie_id');
    }
}
