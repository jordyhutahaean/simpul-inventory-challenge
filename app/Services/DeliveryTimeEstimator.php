<?php

namespace App\Services;

use App\Models\DeliveryRecord;
use Carbon\Carbon;

class DeliveryTimeEstimator
{
    private ?array $byWarehouse = null; //Initiate 

    /** Estimated minutes for a warehouse at a given moment, or null if no history. */
    public function estimate(int $warehouseId, Carbon $when): ?float
    {
        $records = $this->records()[$warehouseId] ?? [];
        if (empty($records)) return null;

        $bandwidth = max((float) config('allocation.eta.bandwidth_hours', 3.0), 0.1);
        $prior     = (float) config('allocation.eta.prior_weight', 1.0);

        $target = ($when->dayOfWeekIso - 1) * 24 + $when->hour + $when->minute / 60;
        $mean   = array_sum(array_column($records, 'minutes')) / count($records);

        $num = $prior * $mean;
        $den = $prior;

        foreach ($records as $r) {
            $gap = abs($r['how'] - $target);
            $gap = min($gap, 168 - $gap);             // the week wraps around
            $w   = exp(-$gap / $bandwidth);
            $num += $w * $r['minutes'];
            $den += $w;
        }

        return $num / $den;
    }

    private function records(): array
    {
        return $this->byWarehouse ??= DeliveryRecord::all()
            ->groupBy('warehouse_id')
            ->map(fn ($g) => $g->map(fn ($r) => [
                'how'     => $r->day_of_week * 24 + $r->hour,
                'minutes' => $r->duration_minutes,
            ])->all())
            ->all();
    }
}