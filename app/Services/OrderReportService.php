<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class OrderReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters): array
    {
        [$from, $to] = $this->dateRange($filters);
        $query = $this->ordersQuery($from, $to);
        $revenueQuery = (clone $query)->where('payment_status', 'paid');

        $timeline = (clone $query)
            ->selectRaw('DATE(created_at) as order_date, COUNT(*) as orders_count')
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN payment_status = ? THEN total ELSE 0 END), 0) as revenue',
                ['paid']
            )
            ->groupBy('order_date')
            ->orderBy('order_date')
            ->get()
            ->map(fn ($row): array => [
                'date' => $row->order_date,
                'orders' => (int) $row->orders_count,
                'revenue' => (float) $row->revenue,
            ])
            ->all();

        $statusCounts = (clone $query)
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total): int => (int) $total)
            ->all();

        $topProducts = OrderItem::query()
            ->select('product_name_snapshot')
            ->selectRaw('SUM(qty) as units_sold')
            ->selectRaw('SUM(subtotal) as revenue')
            ->whereHas('order', fn ($orderQuery) => $orderQuery
                ->whereBetween('created_at', [$from, $to])
                ->where('payment_status', 'paid'))
            ->groupBy('product_name_snapshot')
            ->orderByDesc('units_sold')
            ->limit(10)
            ->get()
            ->map(fn (OrderItem $item): array => [
                'product' => $item->product_name_snapshot,
                'units_sold' => (int) $item->units_sold,
                'revenue' => (float) $item->revenue,
            ])
            ->all();

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'total_orders' => (clone $query)->count(),
            'total_revenue' => (float) $revenueQuery->sum('total'),
            'average_order_value' => (float) ($revenueQuery->avg('total') ?? 0),
            'status_counts' => $statusCounts,
            'timeline' => $timeline,
            'sales_chart' => $this->salesChart($timeline, $from, $to, $filters['period'] ?? 'month'),
            'top_products' => $topProducts,
        ];
    }

    /**
     * @param  array<int, array{date: string, orders: int, revenue: float}>  $timeline
     * @return array<int, array{label: string, orders: int, revenue: float}>
     */
    private function salesChart(array $timeline, CarbonImmutable $from, CarbonImmutable $to, string $period): array
    {
        $days = (int) $from->startOfDay()->diffInDays($to->startOfDay()) + 1;
        $unit = match (true) {
            $period === 'year', $days > 180 && $days <= 1095 => 'month',
            $days > 1095 => 'year',
            $days > 45 => 'week',
            default => 'day',
        };

        $bucketStart = match ($unit) {
            'week' => $from->startOfWeek(),
            'month' => $from->startOfMonth(),
            'year' => $from->startOfYear(),
            default => $from->startOfDay(),
        };
        $step = match ($unit) {
            'week' => 'addWeek',
            'month' => 'addMonth',
            'year' => 'addYear',
            default => 'addDay',
        };

        $chart = [];

        for ($date = $bucketStart; $date <= $to; $date = $date->{$step}()) {
            $key = match ($unit) {
                'week' => $date->format('o-W'),
                'month' => $date->format('Y-m'),
                'year' => $date->format('Y'),
                default => $date->toDateString(),
            };

            $chart[$key] = [
                'label' => match ($unit) {
                    'month' => $date->format('m/Y'),
                    'year' => $date->format('Y'),
                    default => $date->format('d/m'),
                },
                'orders' => 0,
                'revenue' => 0.0,
            ];
        }

        foreach ($timeline as $point) {
            $date = CarbonImmutable::parse($point['date']);
            $key = match ($unit) {
                'week' => $date->format('o-W'),
                'month' => $date->format('Y-m'),
                'year' => $date->format('Y'),
                default => $date->toDateString(),
            };

            $chart[$key]['orders'] += $point['orders'];
            $chart[$key]['revenue'] += $point['revenue'];
        }

        return array_values($chart);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function ordersForReport(array $filters): Builder
    {
        [$from, $to] = $this->dateRange($filters);

        return $this->ordersQuery($from, $to)->with(['user:id,name,email', 'payment.confirmedBy:id,name']);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function dateRange(array $filters): array
    {
        $period = $filters['period'] ?? 'month';

        if ($period === 'custom') {
            return [
                CarbonImmutable::parse($filters['start_date'])->startOfDay(),
                CarbonImmutable::parse($filters['end_date'])->endOfDay(),
            ];
        }

        $today = CarbonImmutable::now();

        return match ($period) {
            'day' => [$today->startOfDay(), $today->endOfDay()],
            'week' => [$today->startOfWeek(), $today->endOfWeek()],
            'year' => [$today->startOfYear(), $today->endOfYear()],
            default => [$today->startOfMonth(), $today->endOfMonth()],
        };
    }

    private function ordersQuery(CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return Order::query()->whereBetween('created_at', [$from, $to]);
    }
}
