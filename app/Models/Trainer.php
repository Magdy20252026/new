<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\File;

#[Fillable(['branch_id', 'name', 'phone', 'password', 'hourly_rate', 'transfer_number', 'transfer_type'])]
#[Hidden(['password'])]
class Trainer extends Model
{
    public const TRANSFER_TYPE_WALLET = 'wallet';

    public const TRANSFER_TYPE_INSTAPAY = 'instapay';

    public function transferTypeLabel(): string
    {
        return self::transferTypeOptions()[$this->transfer_type] ?? $this->transfer_type;
    }

    public static function transferTypeOptions(): array
    {
        return [
            self::TRANSFER_TYPE_WALLET => 'محفظة',
            self::TRANSFER_TYPE_INSTAPAY => 'انستا باي',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function trainerFiles(): HasMany
    {
        return $this->hasMany(TrainerFile::class)->orderBy('title');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $trainer): void {
            $trainer->trainerFiles()->get()->each->delete();
            File::deleteDirectory(public_path('uploads/trainers/'.$trainer->id));
        });
    }

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
            'password' => 'hashed',
        ];
    }
}
