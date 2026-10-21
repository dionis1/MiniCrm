<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    public const STAGES = ['new', 'contacted', 'proposal', 'closed_won', 'closed_lost'];

    protected $fillable = [
        'user_id', 'name', 'email', 'phone', 'company_name', 'stage',
        'deal_value', 'probability', 'version', 'converted_company_id',
    ];

    protected function casts(): array
    {
        return ['deal_value' => 'decimal:2', 'probability' => 'integer', 'version' => 'integer', 'converted_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_company_id');
    }
}
