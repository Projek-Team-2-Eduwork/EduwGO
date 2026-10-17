<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PendapatanExport;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $dashboardService)
    {
        $request->validate([
            'metode' => ['nullable', 'in:xendit_invoice,cash'],
            'branch' => ['nullable', 'integer'],
        ]);

        $branchId = $request->user()->effectiveBranchId($request->integer('branch') ?: null);

        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->endOfMonth()->toDateString());
        $tipe = $request->input('tipe');
        $metode = $request->input('metode');

        $from = Carbon::parse($dari)->startOfDay();
        $to = Carbon::parse($sampai)->endOfDay();

        $topVehicles = $dashboardService->topVehicles($branchId);
        $stats = $dashboardService->stats($branchId);
        $revenue = $dashboardService->revenue($from, $to, $tipe, $metode, $branchId);
        $recentBookings = $dashboardService->recentBookings($branchId);
        $vehicleTypes = $dashboardService->vehicleTypes();
        $branches = $request->user()->isSuperadmin() ? Branch::orderBy('name')->pluck('name', 'id') : collect();

        return view('admin.dashboard', compact(
            'topVehicles',
            'stats',
            'revenue',
            'recentBookings',
            'vehicleTypes',
            'dari',
            'sampai',
            'tipe',
            'metode',
            'branchId',
            'branches',
        ));
    }

    public function export(Request $request)
    {
        $request->validate([
            'dari' => ['date'],
            'sampai' => ['date'],
            'tipe' => ['nullable', 'integer'],
            'metode' => ['nullable', 'in:xendit_invoice,cash'],
            'branch' => ['nullable', 'integer'],
        ]);

        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->endOfMonth()->toDateString());
        $tipe = $request->input('tipe');
        $metode = $request->input('metode');
        $branchId = $request->user()->effectiveBranchId($request->integer('branch') ?: null);

        $from = Carbon::parse($dari)->startOfDay();
        $to = Carbon::parse($sampai)->endOfDay();

        return Excel::download(
            new PendapatanExport($from, $to, $tipe, $metode, $branchId),
            'pendapatan-'.$from->format('Y-m-d').'-'.$to->format('Y-m-d').'.xlsx'
        );
    }
}
