<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalaryPeriod;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;

class BiMonthlyController extends Controller
{
    public function index(Request $request)
    {
        // Fetch Salary Period
        $biMonthlyReports = SalaryPeriod::orderBy('start_date', 'desc')
            ->get()
            ->toArray();

        // Initialize the base props
        $props = [
            'biMonthlyReports' => $biMonthlyReports,
        ];

        return Inertia::render('BiMonthlyView', $props);
    }

    private function getPeriodInfo()
    {
        $today = Carbon::today();
        if ($today->day >= 16) {
            return ['start' => $today->copy()->startOfMonth(), 'end' => $today->copy()->day(15)];
        }
        return ['start' => $today->copy()->subMonth()->day(16), 'end' => $today->copy()->subMonth()->endOfMonth()];
    }

    public function show(SalaryPeriod $period)
    {
        

        return response()->json([
            
        ]);
    }
}
