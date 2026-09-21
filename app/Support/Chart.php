<?php

namespace App\Support;

/**
 * Works out the geometry for the small inline-SVG charts on the analytics
 * screens, so the Blade templates only have to draw what this returns.
 *
 * Follows the house chart specs: 2px lines, a 10% area wash, hairline
 * gridlines, rounded numbers on the y-axis and at most eight x labels.
 */
class Chart
{
    public const WIDTH = 1000;

    public const HEIGHT = 260;

    /**
     * @param  array<int, array<string, mixed>>  $rows  buckets with a 'label', 'short' and one key per series
     * @param  array<int, array{key: string, label: string, color: string}>  $series
     */
    public static function timeSeries(array $rows, array $series, int $width = self::WIDTH, int $height = self::HEIGHT): array
    {
        $pad = ['left' => 54, 'right' => 18, 'top' => 18, 'bottom' => 34];
        $plotWidth = $width - $pad['left'] - $pad['right'];
        $plotHeight = $height - $pad['top'] - $pad['bottom'];
        $count = count($rows);

        $max = 0;
        foreach ($rows as $row) {
            foreach ($series as $definition) {
                $max = max($max, (int) ($row[$definition['key']] ?? 0));
            }
        }

        [$top, $step] = self::niceScale($max);

        $x = function (int $index) use ($count, $pad, $plotWidth): float {
            if ($count <= 1) {
                return $pad['left'] + $plotWidth / 2;
            }

            return $pad['left'] + ($index / ($count - 1)) * $plotWidth;
        };

        $y = fn (float $value): float => $pad['top'] + $plotHeight - ($top > 0 ? ($value / $top) * $plotHeight : 0);

        // Y axis
        $yTicks = [];
        for ($value = 0; $value <= $top + 0.0001; $value += $step) {
            $yTicks[] = [
                'value' => $value,
                'y' => round($y($value), 2),
                'label' => number_format($value),
            ];
        }

        // X axis — at most eight labels, always including the last bucket
        $xTicks = [];
        if ($count > 0) {
            $every = max(1, (int) ceil($count / 8));

            foreach ($rows as $index => $row) {
                $isLast = $index === $count - 1;

                if (($index % $every === 0 || $isLast) && ($row['short'] ?? '') !== '') {
                    $xTicks[] = [
                        'x' => round($x($index), 2),
                        'label' => $row['short'],
                        'anchor' => $isLast && $count > 1 ? 'end' : ($index === 0 ? 'start' : 'middle'),
                    ];
                }
            }

            // Drop a label that would collide with the final one.
            $total = count($xTicks);
            if ($total >= 2 && ($xTicks[$total - 1]['x'] - $xTicks[$total - 2]['x']) < 52) {
                array_splice($xTicks, $total - 2, 1);
            }
        }

        // Series paths
        $plotted = [];
        foreach ($series as $definition) {
            $points = [];

            foreach ($rows as $index => $row) {
                $value = (int) ($row[$definition['key']] ?? 0);
                $points[] = [
                    'x' => round($x($index), 2),
                    'y' => round($y($value), 2),
                    'value' => $value,
                ];
            }

            $line = '';
            foreach ($points as $index => $point) {
                $line .= ($index === 0 ? 'M' : 'L').$point['x'].' '.$point['y'];
            }

            $area = '';
            if ($line !== '' && $count > 1) {
                $baseline = round($pad['top'] + $plotHeight, 2);
                $area = $line
                    .'L'.$points[$count - 1]['x'].' '.$baseline
                    .'L'.$points[0]['x'].' '.$baseline.'Z';
            }

            $plotted[] = $definition + [
                'points' => $points,
                'line' => $line,
                'area' => $area,
                'last' => $points ? $points[$count - 1] : null,
                'total' => array_sum(array_column($points, 'value')),
            ];
        }

        // Invisible hover columns, one per bucket
        $columns = [];
        $columnWidth = $count > 1 ? $plotWidth / ($count - 1) : $plotWidth;
        foreach ($rows as $index => $row) {
            $centre = $x($index);
            $columns[] = [
                'index' => $index,
                'x' => round(max($pad['left'], $centre - $columnWidth / 2), 2),
                'width' => round($count > 1 ? $columnWidth : $plotWidth, 2),
                'centre' => round($centre, 2),
                'label' => $row['label'] ?? '',
                'values' => array_map(
                    fn ($definition) => ['label' => $definition['label'], 'color' => $definition['color'], 'value' => (int) ($row[$definition['key']] ?? 0)],
                    $series
                ),
            ];
        }

        return [
            'width' => $width,
            'height' => $height,
            'pad' => $pad,
            'plot' => ['width' => $plotWidth, 'height' => $plotHeight, 'bottom' => $pad['top'] + $plotHeight],
            'max' => $top,
            'yTicks' => $yTicks,
            'xTicks' => $xTicks,
            'series' => $plotted,
            'columns' => $columns,
            'isEmpty' => $max === 0,
        ];
    }

    /**
     * A rounded axis top and step (1 / 2 / 5 × 10^n) so labels read cleanly.
     *
     * @return array{0: float, 1: float}
     */
    public static function niceScale(int $max, int $ticks = 4): array
    {
        if ($max <= 0) {
            return [4, 1];
        }

        $rough = $max / $ticks;
        $magnitude = 10 ** floor(log10(max($rough, 0.0001)));
        $normalised = $rough / $magnitude;

        $step = match (true) {
            $normalised <= 1 => 1,
            $normalised <= 2 => 2,
            $normalised <= 5 => 5,
            default => 10,
        } * $magnitude;

        // Visits are whole numbers, so never label the axis in fractions.
        $step = max(1, round($step));

        return [ceil($max / $step) * $step, $step];
    }
}
