<?php

namespace App\Domain\Scheduling;

use InvalidArgumentException;

readonly class TimeRange
{
    public function __construct(
        public int $startMinutes,
        public int $endMinutes,
    ) {
        if ($this->startMinutes < 0 || $this->endMinutes > 1440 || $this->startMinutes >= $this->endMinutes) {
            throw new InvalidArgumentException(
                "Intervalo horario inválido: {$this->startMinutes}..{$this->endMinutes}.",
            );
        }
    }

    public static function fromTimeStrings(string $startsAt, string $endsAt): self
    {
        return new self(
            startMinutes: self::parseMinutes($startsAt),
            endMinutes: self::parseMinutes($endsAt),
        );
    }

    public function overlaps(self $other): bool
    {
        return $this->startMinutes < $other->endMinutes
            && $other->startMinutes < $this->endMinutes;
    }

    public function contains(self $other): bool
    {
        return $this->startMinutes <= $other->startMinutes
            && $other->endMinutes <= $this->endMinutes;
    }

    public function durationMinutes(): int
    {
        return $this->endMinutes - $this->startMinutes;
    }

    public function durationHours(): float
    {
        return $this->durationMinutes() / 60.0;
    }

    public function format(): string
    {
        return self::formatMinutes($this->startMinutes).'–'.self::formatMinutes($this->endMinutes);
    }

    /**
     * Une intervalos traslapados o contiguos para evaluar cobertura continua.
     *
     * @param  list<self>  $intervals
     * @return list<self>
     */
    public static function mergeIntervals(array $intervals): array
    {
        if ($intervals === []) {
            return [];
        }

        usort(
            $intervals,
            static fn (self $a, self $b): int => [$a->startMinutes, $a->endMinutes]
                <=> [$b->startMinutes, $b->endMinutes],
        );

        $merged = [$intervals[0]];
        $count = count($intervals);

        for ($i = 1; $i < $count; $i++) {
            $current = $intervals[$i];
            $lastIndex = count($merged) - 1;
            $last = $merged[$lastIndex];

            if ($current->startMinutes <= $last->endMinutes) {
                $merged[$lastIndex] = new self(
                    startMinutes: $last->startMinutes,
                    endMinutes: max($last->endMinutes, $current->endMinutes),
                );
            } else {
                $merged[] = $current;
            }
        }

        return $merged;
    }

    public static function dayLabel(int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
            default => "Día {$dayOfWeek}",
        };
    }

    private static function parseMinutes(string $time): int
    {
        $parts = explode(':', trim($time));
        $hours = (int) $parts[0];
        $minutes = (int) ($parts[1] ?? 0);

        return ($hours * 60) + $minutes;
    }

    private static function formatMinutes(int $totalMinutes): string
    {
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
