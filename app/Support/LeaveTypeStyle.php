<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Colour + icon for a leave type, inferred from its name/code so common
 * types look the same everywhere (Sick = rose stethoscope, Casual = sky sun…).
 * Unknown types get a stable colour from a hash of the code.
 */
class LeaveTypeStyle
{
    /** keyword => [tone, icon], first match wins. */
    public const RULES = [
        'sick' => ['rose', 'ti-stethoscope'],
        'medical' => ['rose', 'ti-stethoscope'],
        'casual' => ['sky', 'ti-sun'],
        'earned' => ['success', 'ti-award'],
        'privilege' => ['success', 'ti-award'],
        'annual' => ['success', 'ti-plane'],
        'vacation' => ['teal', 'ti-plane'],
        'maternity' => ['pink', 'ti-mood-kid'],
        'paternity' => ['violet', 'ti-mood-kid'],
        'parental' => ['violet', 'ti-mood-kid'],
        'bereavement' => ['slate', 'ti-leaf'],
        'marriage' => ['pink', 'ti-heart'],
        'birthday' => ['amber', 'ti-gift'],
        'comp' => ['amber', 'ti-repeat'],
        'study' => ['indigo', 'ti-school'],
        'exam' => ['indigo', 'ti-school'],
        'unpaid' => ['slate', 'ti-coin'],
        'loss of pay' => ['slate', 'ti-coin'],
        'work from home' => ['sky', 'ti-home'],
    ];

    public const FALLBACK_TONES = ['indigo', 'teal', 'pink', 'amber', 'violet', 'sky', 'lime'];

    /** @return array{tone: string, icon: string} */
    public static function for(?string $name, ?string $code = null): array
    {
        $haystack = Str::lower(trim((string) $name));

        foreach (self::RULES as $keyword => [$tone, $icon]) {
            if (str_contains($haystack, $keyword)) {
                return ['tone' => $tone, 'icon' => $icon];
            }
        }

        $key = Str::lower((string) ($code ?: $name));

        return [
            'tone' => self::FALLBACK_TONES[crc32($key) % count(self::FALLBACK_TONES)],
            'icon' => 'ti-calendar-off',
        ];
    }
}
