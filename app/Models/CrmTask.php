<?php

namespace App\Models;

use App\Enums\TaskPriority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmTask extends Model
{
    use HasFactory;

    protected $table = 'crm_tasks';

    protected $fillable = ['user_id', 'lead_id', 'company_id', 'title', 'description', 'priority', 'due_at', 'completed_at'];

    protected function casts(): array
    {
        return ['priority' => TaskPriority::class, 'due_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'company_id');
    }
}
