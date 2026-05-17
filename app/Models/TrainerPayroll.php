<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'branch_id',
    'trainer_id',
    'period_start',
    'period_end',
    'hours',
    'hourly_rate',
    'total_amount',
    'status',
    'paid_at',
    'held_at',
])]
class TrainerPayroll extends Model
{
    public const STATUS_PAID = 'paid';

    public const STATUS_HELD = 'held';

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'hours' => 'decimal:2',
            'hourly_rate' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'held_at' => 'datetime',
        ];
    }
}
