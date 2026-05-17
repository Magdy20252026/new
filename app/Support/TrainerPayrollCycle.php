<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class TrainerPayrollCycle
{
    protected const WEEKDAY_MAP = [
        'sunday' => CarbonInterface::SUNDAY,
        'monday' => CarbonInterface::MONDAY,
        'tuesday' => CarbonInterface::TUESDAY,
        'wednesday' => CarbonInterface::WEDNESDAY,
        'thursday' => CarbonInterface::THURSDAY,
        'friday' => CarbonInterface::FRIDAY,
        'saturday' => CarbonInterface::SATURDAY,
    ];

    public static function currentPeriod(?CarbonInterface $reference = null, ?array $paymentWeek = null): array
    {
        $paymentWeek ??= ControlPanel::trainerPaymentWeek();
        $referenceDate = CarbonImmutable::parse(($reference ?? now())->toDateString());
        $start = static::periodStartForReference($referenceDate, $paymentWeek);
        $period = static::periodFromStart($start, $paymentWeek);

        if ($referenceDate->gt($period['end'])) {
            $period = static::periodFromStart($start->addWeek(), $paymentWeek);
        }

        return $period;
    }

    public static function periodStartForReference(CarbonInterface $reference, array $paymentWeek): CarbonImmutable
    {
        $referenceDate = CarbonImmutable::parse($reference->toDateString());
        $startIndex = static::weekdayIndex($paymentWeek['start_day']);
        $daysBack = ($referenceDate->dayOfWeek - $startIndex + 7) % 7;

        return $referenceDate->subDays($daysBack);
    }

    public static function periodFromStart(CarbonInterface $start, array $paymentWeek): array
    {
        $startDate = CarbonImmutable::parse($start->toDateString());
        $duration = (static::weekdayIndex($paymentWeek['end_day']) - static::weekdayIndex($paymentWeek['start_day']) + 7) % 7;

        return [
            'start' => $startDate,
            'end' => $startDate->addDays($duration),
            'start_label' => $paymentWeek['start_label'] ?? ControlPanel::trainerPaymentWeekDayOptions()[$paymentWeek['start_day']],
            'end_label' => $paymentWeek['end_label'] ?? ControlPanel::trainerPaymentWeekDayOptions()[$paymentWeek['end_day']],
        ];
    }

    protected static function weekdayIndex(string $day): int
    {
        return static::WEEKDAY_MAP[$day] ?? CarbonInterface::SATURDAY;
    }
}
