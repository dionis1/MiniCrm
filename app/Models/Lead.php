<?php

namespace App\Models;

use App\Enums\LeadStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'email', 'phone', 'company_name', 'stage',
        'deal_value', 'probability', 'version', 'converted_company_id',
    ];

    protected function casts(): array
    {
        return ['stage' => LeadStage::class, 'deal_value' => 'decimal:2', 'probability' => 'integer', 'version' => 'integer', 'converted_at' => 'datetime'];
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
