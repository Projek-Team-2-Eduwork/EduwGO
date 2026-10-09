<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PendapatanExport;
use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $dashboardService)
    {
        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->endOfMonth()->toDateString());
        $tipe = $request->input('tipe');

        $from = Carbon::parse($dari)->startOfDay();
        $to = Carbon::parse($sampai)->endOfDay();

        $topVehicles = $dashboardService->topVehicles();
        $stats = $dashboardService->stats();
        $revenue = $dashboardService->revenue($from, $to, $tipe);
        $recentBookings = $dashboardService->recentBookings();
        $vehicleTypes = $dashboardService->vehicleTypes();

        return view('admin.dashboard', compact(
            'topVehicles', 'stats', 'revenue', 'recentBookings', 'vehicleTypes', 'dari', 'sampai', 'tipe'
        ));
    }

    public function export(Request $request)
    {
        $request->validate([
            'dari' => ['date'],
            'sampai' => ['date'],
            'tipe' => ['nullable', 'integer'],
        ]);

        $dari = $request->input('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->input('sampai', now()->endOfMonth()->toDateString());
        $tipe = $request->input('tipe');

        $from = Carbon::parse($dari)->startOfDay();
        $to = Carbon::parse($sampai)->endOfDay();

        return Excel::download(
            new PendapatanExport($from, $to, $tipe),
            'pendapatan-'.$from->format('Y-m-d').'-'.$to->format('Y-m-d').'.xlsx'
        );
    }
}
