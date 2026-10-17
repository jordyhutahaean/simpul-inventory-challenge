<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Warehouse;
use Carbon\Carbon;


class WarehouseAllocator
{
    /**
     * @param array  
     */
    private array $etas = [];
    private float $maxEta = 1.0;

    public function __construct(private DeliveryTimeEstimator $estimator) {}

    public function allocate(array $lines, ?Carbon $when = null): array
    {
        $when ??= now();
        // 1. Merge duplicate lines, drop non-positive quantities.
        $demand = [];
        foreach ($lines as $line) {
            $pid = (int) $line['product_id'];
            $qty = (int) $line['quantity'];
            if ($qty > 0) {
                $demand[$pid] = ($demand[$pid] ?? 0) + $qty;
            }
        }

        $products   = Product::findMany(array_keys($demand))->keyBy('id');
        $warehouses = Warehouse::with('inventories')->orderBy('distance_km')->get();
        $maxDist    = max((float) $warehouses->max('distance_km'), 1.0);

        // 2. Eligibility matrix: units each warehouse can actually supply.
        $avail = [];
        foreach ($warehouses as $w) {
            foreach ($products as $p) {
                $stock = $w->inventories->firstWhere('product_id', $p->id)?->quantity ?? 0;
                $blocked = $p->requires_cold_chain && !$w->has_refrigerated_storage;
                $avail[$w->id][$p->id] = $blocked ? 0 : $stock;
            }
        }

        // 3. Single warehouse that covers everything?
        $full = $warehouses->filter(function ($w) use ($demand, $avail) {
            foreach ($demand as $pid => $qty) {
                if ($avail[$w->id][$pid] < $qty) return false;
            }
            return true;
        });

        if ($full->isNotEmpty()) {
            $best = $full->sortByDesc(
                fn ($w) => $this->score($w, $demand, $avail, $products, $maxDist)
            )->first();

            $take = [];
            foreach ($demand as $pid => $qty) $take[$pid] = $qty;

            $allocations = [$this->buildAllocation($best, $take, $products, $demand, $avail, $maxDist)];

            return $this->result('single', [], $allocations, $products, $warehouses, $demand, $avail, $best);
        }

        // 4. Greedy split.
        $remaining   = $demand;
        $allocations = [];
        $used        = [];

        while (!empty($remaining)) {
            $best = null;
            $bestScore = -1;

            foreach ($warehouses as $w) {
                if (isset($used[$w->id])) continue;

                $canSupply = false;
                foreach ($remaining as $pid => $need) {
                    if ($avail[$w->id][$pid] > 0) { $canSupply = true; break; }
                }
                if (!$canSupply) continue;

                $s = $this->score($w, $remaining, $avail, $products, $maxDist);
                if ($s > $bestScore) { $bestScore = $s; $best = $w; }
            }

            if (!$best) break; // nothing left anywhere

            $take = [];
            foreach ($remaining as $pid => $need) {
                $qty = min($need, $avail[$best->id][$pid]);
                if ($qty > 0) {
                    $take[$pid] = $qty;
                    $remaining[$pid] -= $qty;
                    if ($remaining[$pid] <= 0) unset($remaining[$pid]);
                }
            }

            $allocations[] = $this->buildAllocation($best, $take, $products, $remaining + $take, $avail, $maxDist);
            $used[$best->id] = true;
        }
          $this->etas = [];
            foreach ($warehouses as $w) {
                $this->etas[$w->id] = $this->estimator->estimate($w->id, $when);
            }
            $known = array_filter($this->etas, fn ($e) => $e !== null);
            $this->maxEta = $known ? max($known) : 1.0;

        return $this->result('split', $remaining, $allocations, $products, $warehouses, $demand, $avail, null);
    }

    /**
     * Score 0..1. Higher is better.
     * Cold-chain fit only counts if the warehouse supplies a cold-chain item.
     */
   private function score($w, array $remaining, array $avail, $products, float $maxDist): float
    {
        $wt = config('allocation.weights');

        $coverage = 0;
        $cold = [];
        foreach ($remaining as $pid => $need) {
            $take = min($need, $avail[$w->id][$pid]);
            $coverage += $take / $need;
            if ($take > 0 && $products[$pid]->requires_cold_chain) {
                $cold[] = $w->has_refrigerated_vans ? 1.0 : 0.5;
            }
        }
        $coverage /= max(count($remaining), 1);

        $parts = [
            'distance' => 1 - ((float) $w->distance_km / $maxDist),
            'coverage' => $coverage,
        ];
        if (($this->etas[$w->id] ?? null) !== null) {
            $parts['travel_time'] = 1 - ($this->etas[$w->id] / $this->maxEta);
        }
        if ($cold) {
            $parts['cold_chain'] = array_sum($cold) / count($cold);
        }

        $sum = 0; $weights = 0;
        foreach ($parts as $k => $v) {
            $sum += ($wt[$k] ?? 0) * $v;
            $weights += $wt[$k] ?? 0;
        }
        return $sum / max($weights, 1e-9);
    }

    private function buildAllocation($w, array $take, $products, array $scoredAgainst, array $avail, float $maxDist): array
    {
        $items = [];
        $warnings = [];

        foreach ($take as $pid => $qty) {
            $p = $products[$pid];
            $items[] = [
                'product_id' => $p->id,
                'sku'        => $p->sku,
                'name'       => $p->name,
                'quantity'   => $qty,
            ];
            if ($p->requires_cold_chain && !$w->has_refrigerated_vans) {
                $warnings[] = "{$p->name} ships from {$w->code}, which has refrigerated storage but only standard vehicles.";
            }
        }

        return [
            'warehouse' => [
                'id' => $w->id, 'code' => $w->code, 'name' => $w->name,
                'location' => $w->location, 'distance_km' => $w->distance_km,
            ],
            'items'    => $items,
            'score'    => round($this->score($w, $scoredAgainst, $avail, $products, $maxDist), 3),
            'warnings' => $warnings,
        ];
    }

    private function result(string $strategy, array $remaining, array $allocations, $products, $warehouses, array $demand, array $avail, $singleChoice): array
    {
        $shortfalls = [];
        foreach ($remaining as $pid => $qty) {
            $shortfalls[] = [
                'sku' => $products[$pid]->sku,
                'name' => $products[$pid]->name,
                'missing' => $qty,
            ];
        }

        $status = empty($allocations) ? 'unfulfillable' : (empty($shortfalls) ? 'full' : 'partial');

        $explanation = [];

        if ($strategy === 'single') {
            $a = $allocations[0]['warehouse'];
            $explanation[] = "{$a['code']} ({$a['location']}, {$a['distance_km']} km) can cover the entire order, so it ships as one consignment.";

            foreach ($warehouses as $w) {
                if ($w->id === $singleChoice->id) continue;
                $explanation[] = "{$w->code} not chosen: " . $this->rejectionReason($w, $demand, $avail, $products) . '.';
            }
        } elseif (!empty($allocations)) {
            $explanation[] = 'No single warehouse could cover the whole order, so it was split across '
                . count($allocations) . ' warehouses, picking the best-scoring one for what was still missing at each step.';
            foreach ($allocations as $a) {
                $wh = $a['warehouse'];
                $list = implode(', ', array_map(fn ($i) => "{$i['quantity']} x {$i['name']}", $a['items']));
                $explanation[] = "{$wh['code']} ({$wh['location']}, {$wh['distance_km']} km): {$list}.";
            }
        }

        foreach ($shortfalls as $s) {
            $explanation[] = "Shortfall: {$s['missing']} x {$s['name']} could not be supplied from any eligible warehouse.";
        }

        if ($status === 'unfulfillable') {
            $explanation[] = 'No warehouse can supply any item in this order.';
        }

        return [
            'status'      => $status,
            'strategy'    => $strategy,
            'allocations' => $allocations,
            'shortfalls'  => $shortfalls,
            'explanation' => $explanation,
        ];

        $eta = $this->etas[$singleChoice->id] ?? null;
        $etaText = $eta ? sprintf(' Estimated delivery time at this hour: about %d min, based on past deliveries.', round($eta)) : '';
        $explanation[] = "{$a['code']} ({$a['location']}, {$a['distance_km']} km) can cover the entire order, so it ships as one consignment.{$etaText}";
    }

    private function rejectionReason($w, array $demand, array $avail, $products): string
    {
        $reasons = [];
        foreach ($demand as $pid => $qty) {
            $p = $products[$pid];
            if ($p->requires_cold_chain && !$w->has_refrigerated_storage) {
                $reasons[] = "no refrigerated storage for {$p->name}";
            } elseif ($avail[$w->id][$pid] < $qty) {
                $reasons[] = "only {$avail[$w->id][$pid]} of {$qty} {$p->name} in stock";
            }
        }

        return $reasons
            ? implode('; ', $reasons)
            : 'could also cover the order, but scored lower (farther away or weaker cold-chain fit)';
    }
}