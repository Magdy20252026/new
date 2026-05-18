<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

#[Fillable([
    'branch_id',
    'training_group_id',
    'serial_number',
    'barcode',
    'name',
    'birth_year',
    'father_phone',
    'mother_phone',
    'subscription_start_date',
    'subscription_end_date',
    'group_price',
    'amount_paid',
    'remaining_amount',
    'excluded_from_financial_totals',
])]
class Swimmer extends Model
{
    public static function nextSerialNumber(): int
    {
        return max(1001, ((int) static::query()->max('serial_number')) + 1);
    }

    public static function calculateAge(int $birthYear): int
    {
        return max(0, Carbon::now()->year - $birthYear);
    }

    public static function calculateSubscriptionEndDate(string $startDate, TrainingGroup $trainingGroup): string
    {
        $weeks = (int) ceil(
            max(1, $trainingGroup->available_training_days) / max(1, $trainingGroup->training_days_per_week)
        );

        return Carbon::parse($startDate)->addWeeks(max(1, $weeks))->toDateString();
    }

    public static function generateBarcode(int $serialNumber): string
    {
        return (string) $serialNumber;
    }

    public static function supportsFinancialExclusion(): bool
    {
        return Schema::hasColumn((new static)->getTable(), 'excluded_from_financial_totals');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function trainingGroup(): BelongsTo
    {
        return $this->belongsTo(TrainingGroup::class);
    }

    public function swimmerFiles(): HasMany
    {
        return $this->hasMany(SwimmerFile::class)
            ->orderBy('type')
            ->orderBy('title');
    }

    public function age(): int
    {
        return self::calculateAge((int) $this->birth_year);
    }

    public function playerPhotoFile(): ?SwimmerFile
    {
        $files = $this->relationLoaded('swimmerFiles')
            ? $this->swimmerFiles
            : $this->swimmerFiles()->get();

        return $files->firstWhere('type', SwimmerFile::TYPE_PLAYER_PHOTO);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $swimmer): void {
            $swimmer->swimmerFiles()->get()->each->delete();
            File::deleteDirectory(public_path('uploads/swimmers/'.$swimmer->id));
        });
    }

    protected function casts(): array
    {
        return [
            'subscription_start_date' => 'date',
            'subscription_end_date' => 'date',
            'group_price' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'serial_number' => 'integer',
            'birth_year' => 'integer',
            'excluded_from_financial_totals' => 'boolean',
        ];
    }
}
