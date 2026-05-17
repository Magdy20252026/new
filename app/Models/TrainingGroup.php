<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'branch_id',
    'trainer_id',
    'name',
    'level',
    'training_days_per_week',
    'available_training_days',
    'max_swimmers',
    'price',
    'schedule',
])]
class TrainingGroup extends Model
{
    public static function levelOptions(): array
    {
        return [
            'مدارس سباحة',
            'تجهيزي فرق جديد',
            'تجهيزي فرق A',
            'تجهيزي فرق B',
            'فرق استارات 1 نجمة',
            'فرق استارات 2 نجمة',
            'فرق استارات 3 نجمة',
            'فرق استارات 4 نجمة',
            'فرق ستار 3-4',
            'قطاع بطولة فرق براعم',
            'قطاع بطولة كلاسك',
            'قطاع بطولة زعانف',
            'رجال',
            'سيدات',
        ];
    }

    public static function weekDayOptions(): array
    {
        return [
            'saturday' => 'السبت',
            'sunday' => 'الأحد',
            'monday' => 'الاثنين',
            'tuesday' => 'الثلاثاء',
            'wednesday' => 'الأربعاء',
            'thursday' => 'الخميس',
            'friday' => 'الجمعة',
        ];
    }

    public static function generateName(string $level, string $trainerName, array $schedule): string
    {
        $segments = collect($schedule)
            ->map(function (array $entry): string {
                $dayLabel = self::weekDayOptions()[$entry['day']] ?? $entry['day'];

                return trim($dayLabel.' '.self::normalizeTime($entry['time']));
            })
            ->filter()
            ->values()
            ->all();

        return collect([$level, $trainerName, ...$segments])
            ->filter(fn (?string $value) => filled($value))
            ->implode(' - ');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function scheduleSummary(): string
    {
        return collect($this->schedule ?? [])
            ->map(function (array $entry): string {
                $dayLabel = self::weekDayOptions()[$entry['day']] ?? $entry['day'];

                return trim($dayLabel.' '.self::normalizeTime($entry['time']));
            })
            ->implode(' - ');
    }

    protected function casts(): array
    {
        return [
            'schedule' => 'array',
            'price' => 'decimal:2',
            'training_days_per_week' => 'integer',
            'available_training_days' => 'integer',
            'max_swimmers' => 'integer',
        ];
    }

    protected static function normalizeTime(?string $time): string
    {
        return filled($time) ? mb_substr($time, 0, 5) : '';
    }
}
