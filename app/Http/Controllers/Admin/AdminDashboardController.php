<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminReportRequest;
use App\Services\OrderReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(AdminReportRequest $request, OrderReportService $reports): JsonResponse|View
    {
        $summary = $reports->summary($request->validated());

        if (! $request->expectsJson()) {
            return view('admin.dashboard', compact('summary'));
        }

        return response()->json([
            'data' => $summary,
            'navigation' => [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard'],
                ['label' => 'Users', 'route' => 'admin.users.index'],
                ['label' => 'Products', 'route' => 'admin.products.index'],
                ['label' => 'Orders', 'route' => 'admin.orders.index'],
                ['label' => 'Reports', 'route' => 'admin.reports.orders'],
            ],
        ]);
    }
}
