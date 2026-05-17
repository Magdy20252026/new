<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'branch_id',
    'administrator_id',
    'period_start',
    'period_end',
    'amount',
    'paid_at',
])]
class AdministratorPayroll extends Model
{
    public function administrator(): BelongsTo
    {
        return $this->belongsTo(Administrator::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }
}
