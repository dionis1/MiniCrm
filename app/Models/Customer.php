<?php

namespace App\Models;

use App\Enums\CustomerStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $fillable = ['user_id', 'name', 'email', 'phone', 'website_url', 'status', 'tags'];

    protected function casts(): array
    {
        return ['status' => CustomerStatus::class, 'tags' => 'array'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'companie_id');
    }
}
