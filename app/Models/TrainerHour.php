<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['trainer_id', 'worked_on', 'hours'])]
class TrainerHour extends Model
{
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    protected function casts(): array
    {
        return [
            'worked_on' => 'date',
            'hours' => 'decimal:2',
        ];
    }
}
