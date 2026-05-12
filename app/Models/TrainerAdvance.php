<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['trainer_id', 'advanced_on', 'amount'])]
class TrainerAdvance extends Model
{
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    protected function casts(): array
    {
        return [
            'advanced_on' => 'date',
            'amount' => 'decimal:2',
        ];
    }
}
