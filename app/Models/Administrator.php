<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['branch_id', 'name', 'phone', 'job_title', 'salary'])]
class Administrator extends Model
{
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function administratorPayrolls(): HasMany
    {
        return $this->hasMany(AdministratorPayroll::class)
            ->orderByDesc('period_end')
            ->orderByDesc('created_at');
    }

    protected function casts(): array
    {
        return [
            'salary' => 'decimal:2',
        ];
    }
}
