<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The date window (and bot preference) every analytics figure is measured over.
 */
class ReportRange
{
    public const PRESETS = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        '7days' => 'Last 7 days',
        '30days' => 'Last 30 days',
        '90days' => 'Last 90 days',
        'this_month' => 'This month',
        'last_month' => 'Last month',
        'this_year' => 'This year',
        'all' => 'All time',
    ];

    public function __construct(
        public readonly string $preset,
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly bool $includeBots = false,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $preset = (string) $request->query('range', '30days');
        $includeBots = $request->boolean('bots');

        $customFrom = self::parse($request->query('from'));
        $customTo = self::parse($request->query('to'));

        if ($customFrom || $customTo) {
            $from = ($customFrom ?: $customTo)->copy()->startOfDay();
            $to = ($customTo ?: $customFrom)->copy()->endOfDay();

            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }

            return new self('custom', $from, $to, $includeBots);
        }

        [$from, $to] = self::boundsForPreset($preset);

        return new self(isset(self::PRESETS[$preset]) ? $preset : '30days', $from, $to, $includeBots);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    protected static function boundsForPreset(string $preset): array
    {
        $today = Carbon::today();

        return match ($preset) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            'yesterday' => [$today->copy()->subDay()->startOfDay(), $today->copy()->subDay()->endOfDay()],
            '7days' => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()],
            '90days' => [$today->copy()->subDays(89)->startOfDay(), $today->copy()->endOfDay()],
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfDay()],
            'last_month' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfDay()],
            'all' => [Carbon::create(2000, 1, 1)->startOfDay(), $today->copy()->endOfDay()],
            default => [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()],
        };
    }

    protected static function parse($value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    public function label(): string
    {
        if ($this->preset === 'custom') {
            return $this->from->format('d M Y').' – '.$this->to->format('d M Y');
        }

        if ($this->preset === 'all') {
            return 'All time';
        }

        return self::PRESETS[$this->preset] ?? '';
    }

    public function subtitle(): string
    {
        return $this->isSingleDay()
            ? $this->from->format('l, d F Y')
            : $this->from->format('d M Y').' – '.$this->to->format('d M Y');
    }

    public function dayCount(): int
    {
        return max(1, $this->from->diffInDays($this->to) + 1);
    }

    public function isSingleDay(): bool
    {
        return $this->from->isSameDay($this->to);
    }

    /** The equally long window immediately before this one, for comparisons. */
    public function previous(): self
    {
        $length = $this->from->diffInSeconds($this->to);

        return new self(
            $this->preset,
            $this->from->copy()->subSeconds($length + 1),
            $this->from->copy()->subSecond(),
            $this->includeBots,
        );
    }

    public function comparisonLabel(): string
    {
        return match ($this->preset) {
            'today' => 'vs yesterday',
            'yesterday' => 'vs the day before',
            'this_month' => 'vs the previous period',
            'last_month' => 'vs the month before',
            default => 'vs the previous '.$this->dayCount().' days',
        };
    }

    /** Query string that keeps the current range when linking elsewhere. */
    public function query(array $extra = []): array
    {
        $base = $this->preset === 'custom'
            ? ['from' => $this->from->toDateString(), 'to' => $this->to->toDateString()]
            : ['range' => $this->preset];

        if ($this->includeBots) {
            $base['bots'] = 1;
        }

        return array_merge($base, $extra);
    }
}
