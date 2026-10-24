<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminReportRequest;
use App\Services\OrderReportPdf;
use App\Services\OrderReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminOrderReportController extends Controller
{
    public function index(AdminReportRequest $request, OrderReportService $reports): JsonResponse|View
    {
        $summary = $reports->summary($request->validated());

        if (! $request->expectsJson()) {
            return view('admin.reports.orders', compact('summary'));
        }

        return response()->json(['data' => $summary]);
    }

    public function download(
        AdminReportRequest $request,
        OrderReportService $reports
    ): StreamedResponse {
        $filters = $request->validated();
        $query = $reports->ordersForReport($filters);
        $summary = $reports->summary($filters);
        $filename = 'orders-report-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query, $summary): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, [
                'Order Code',
                'Customer',
                'Email',
                'Status',
                'Payment Status',
                'Payment Channel',
                'Payment Method',
                'Paid At',
                'Confirmed By',
                'Delivery Method',
                'Total',
                'Created At',
            ]);

            $query->orderBy('id')->chunkById(500, function ($orders) use ($handle): void {
                foreach ($orders as $order) {
                    fputcsv($handle, [
                        $order->order_code,
                        $order->user?->name,
                        $order->user?->email,
                        $order->status,
                        $order->payment_status,
                        $order->payment_channel?->value,
                        $order->manual_payment_method?->value ?? $order->payment_type,
                        $order->paid_at?->toIso8601String(),
                        $order->payment?->confirmedBy?->name,
                        $order->delivery_method?->value,
                        $order->total,
                        $order->created_at?->toIso8601String(),
                    ]);
                }
            });

            fputcsv($handle, []);
            fputcsv($handle, ['Periode', $summary['period']['from'].' s.d. '.$summary['period']['to']]);
            fputcsv($handle, ['Total Pesanan', $summary['total_orders']]);
            fputcsv($handle, ['Total Pendapatan (pembayaran paid)', $summary['total_revenue']]);
            fputcsv($handle, ['Rata-rata Pesanan', $summary['average_order_value']]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadPdf(
        AdminReportRequest $request,
        OrderReportService $reports,
        OrderReportPdf $pdf
    ): Response {
        $filters = $request->validated();
        $summary = $reports->summary($filters);
        $orders = $reports->ordersForReport($filters)->lazyById(500);
        $filename = 'orders-report-'.now()->format('Y-m-d-His').'.pdf';

        return response($pdf->render($summary, $orders), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
