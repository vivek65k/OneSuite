<?php

namespace App\Support;

use App\Models\Shift;

/**
 * Shift time arithmetic (overnight-aware) and time-of-day presentation.
 */
class ShiftTime
{
    /** "HH:MM[:SS]" -> minutes since midnight. */
    public static function toMinutes(?string $time): int
    {
        if (! $time) {
            return 0;
        }
        [$h, $m] = array_map('intval', array_pad(explode(':', $time), 2, 0));

        return $h * 60 + $m;
    }

    /** Span in minutes; an end at/before the start means the shift runs past midnight. */
    public static function spanMinutes(?string $start, ?string $end): int
    {
        $s = self::toMinutes($start);
        $e = self::toMinutes($end);

        return $e > $s ? $e - $s : 1440 - $s + $e;
    }

    public static function crossesMidnight(?string $start, ?string $end): bool
    {
        return self::toMinutes($end) <= self::toMinutes($start);
    }

    /** Hours on the clock minus the break. */
    public static function netHours(Shift $shift): float
    {
        return round(max(0, self::spanMinutes($shift->start_time, $shift->end_time) - (int) $shift->break_minutes) / 60, 2);
    }

    public static function format(?string $time): string
    {
        return $time ? date('g:i A', strtotime($time)) : '—';
    }

    /**
     * Time-of-day look for a shift, by its start hour.
     *
     * @return array{tone: string, icon: string, label: string}
     */
    public static function style(Shift $shift): array
    {
        if ($shift->is_night_shift) {
            return ['tone' => 'violet', 'icon' => 'ti-moon', 'label' => 'Night'];
        }

        $hour = intdiv(self::toMinutes($shift->start_time), 60);

        return match (true) {
            $hour < 8 => ['tone' => 'amber', 'icon' => 'ti-sun', 'label' => 'Morning'],
            $hour < 12 => ['tone' => 'indigo', 'icon' => 'ti-briefcase', 'label' => 'Day'],
            $hour < 18 => ['tone' => 'teal', 'icon' => 'ti-clock', 'label' => 'Afternoon'],
            default => ['tone' => 'pink', 'icon' => 'ti-moon', 'label' => 'Evening'],
        };
    }

    /**
     * Segments (as % of a 24h day) for drawing a shift on a timeline; an
     * overnight shift becomes two segments.
     *
     * @return list<array{left: float, width: float}>
     */
    public static function segments(?string $start, ?string $end): array
    {
        $s = self::toMinutes($start);
        $span = self::spanMinutes($start, $end);
        $pct = fn (int $m) => round($m / 1440 * 100, 3);

        if ($s + $span <= 1440) {
            return [['left' => $pct($s), 'width' => $pct($span)]];
        }

        return [
            ['left' => $pct($s), 'width' => $pct(1440 - $s)],
            ['left' => 0.0, 'width' => $pct($s + $span - 1440)],
        ];
    }
}
