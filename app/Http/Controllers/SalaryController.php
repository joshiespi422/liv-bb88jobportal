<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalaryPeriod;
use App\Models\User;
use App\Models\Salary;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Services\SalaryCalculationService;
use Inertia\Inertia;

class SalaryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('userType');
        $typeName = $user->userType->type_name;

        // Fetch Salary History
        $historyPeriods = SalaryPeriod::orderBy('start_date', 'desc')
            ->get()
            ->toArray();

        // Initialize the base props
        $props = [
            'historyPeriods' => $historyPeriods,
        ];

        if ($typeName === 'super_admin') {
            $periodInfo = $this->getPeriodInfo();
            $period = SalaryPeriod::where('start_date', $periodInfo['start']->toDateString())
                ->where('end_date', $periodInfo['end']->toDateString())
                ->first();

            if (!$period) {
                abort(404);
            }

            $employees = User::query()
                ->whereHas('employeeDetails')
                ->whereHas('status', fn($q) => $q->where('status_name', 'active'))
                ->with(['employeeDetails:user_id,current_salary', 'salaries.status', 'salaries.holidays', 'salaries' => function($q) use ($period) {
                    $q->where('salary_period_id', $period?->id);
                }])
                ->select(['id', 'name', 'qr_code', 'position'])
                ->get();

            // Merge s-admin data into props
            $props = array_merge($props, [
                'employees' => $employees,
                'currentPeriod' => $period,
                'periodDates' => [
                    'label' => "{$periodInfo['start']->format('M d')} - {$periodInfo['end']->format('M d, Y')}",
                    'start' => $periodInfo['start']->toDateString(),
                    'end' => $periodInfo['end']->toDateString(),
                ]
            ]);
        }

        return Inertia::render('SalaryView', $props);
    }

    private function getPeriodInfo()
    {
        $today = Carbon::today();
        if ($today->day >= 16) {
            return ['start' => $today->copy()->startOfMonth(), 'end' => $today->copy()->day(15)];
        }
        return ['start' => $today->copy()->subMonth()->day(16), 'end' => $today->copy()->subMonth()->endOfMonth()];
    }

    public function recompute(Request $request, SalaryCalculationService $service)
    {
        $request->validate(['user_id' => 'required|exists:users,id', 'salary_period_id' => 'required']);
        
        $period = SalaryPeriod::findOrFail($request->salary_period_id);
        $user = User::where('id', $request->user_id)
            ->where(function ($query) use ($period) {
                $query->whereHas('salaries', function ($q) use ($period) {
                    $q->where('salary_period_id', $period->id)
                    ->whereHas('status', fn($s) => $s->where('status_name', 'pending'));
                })
                ->orWhereDoesntHave('salaries', function ($q) use ($period) {
                    $q->where('salary_period_id', $period->id);
                });
            })
            ->whereHas('employeeDetails', function ($q) {
                $q->whereNotNull('current_salary');
            })
            ->with('employeeDetails')
            ->firstOrFail();
        
        $service->compute(collect([$user]), $period);

        return back()->with('success', 'Salary re-computed successfully!');
    }

    public function recomputeAll(Request $request, SalaryCalculationService $service)
    {
        $request->validate(['salary_period_id' => 'required']);
        $period = SalaryPeriod::findOrFail($request->salary_period_id);

        // Get all active users who have a PENDING salary in this period
        $users = User::where(function ($query) use ($period) {
                $query->whereHas('salaries', function ($q) use ($period) {
                    $q->where('salary_period_id', $period->id)
                    ->whereHas('status', fn($s) => $s->where('status_name', 'pending'));
                })
                ->orWhereDoesntHave('salaries', function ($q) use ($period) {
                    $q->where('salary_period_id', $period->id);
                });
            })
            ->whereHas('employeeDetails', function ($q) {
                $q->whereNotNull('current_salary');
            })
            ->with('employeeDetails')
            ->get();

        if ($users->isEmpty()) {
            abort(403, 'no users found matching the criteria');
        } else {
            $service->compute($users, $period);
        }

        return back()->with('success', 'All pending salaries re-computed!');
    }

    public function approve(Salary $salary)
    {
        // Authorization
        $user = Auth::user();
        $isSuperAdmin = $user->userType->type_name === 'super_admin';
        if (!$isSuperAdmin) { 
            abort(403, 'not authorized'); 
        }

        // Status guard 
        $pendingStatusId = Status::where('status_name', 'pending')->value('id');
        if ($salary->status_id !== $pendingStatusId) {
            abort(403, 'salary is not pending');
        }

        $salary->status_id = Status::where('status_name', 'approved')->value('id');
        $salary->approver_id = $user->id;
        $salary->save();

        return back()->with('success', 'Salary approved successfully!');
    }

    public function payrollList(SalaryPeriod $period)
    {
        $employeeList = User::whereHas('salaries', function ($query) use ($period) {
            $query->where('salary_period_id', $period->id)
                ->whereHas('status', function ($statusQuery) {
                    $statusQuery->where('status_name', 'approved');
                });
        })
        ->get()
        ->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
        ])
        ->values(); 

        return response()->json([
            'periodId' => $period->id,
            'startDate' => $period->start_date,
            'endDate' => $period->end_date,
            'employeeList' => $employeeList
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, SalaryPeriod $period)
    {
        $employee = User::select('id', 'name', 'qr_code', 'position')
            ->with(['salaries.approver:id,name','salaries.holidays', 'salaries' => function($query) use ($period) {
                $query->where('salary_period_id', $period->id)
                    ->whereHas('status', function($statusQuery) {
                            $statusQuery->where('status_name', 'approved');
                        });
            }])
            ->whereHas('employeeDetails')
            ->findOrFail($id);

        // Authorization
        $user = Auth::user();
        $isSuperAdmin = $user->userType->type_name === 'super_admin';
        $isOwner = $user->id === $employee->id;

        if (!$isSuperAdmin && !$isOwner) {
            abort(403, 'not authorized'); 
        }

        return response()->json([
            'employee' => $employee,
            'startDate' => $period->start_date,
            'endDate' => $period->end_date
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
