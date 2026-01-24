<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Overtime;
use App\Models\Status;

class OvertimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('userType');

        // Build the query based on user permissions and filters
        $query = $this->getOvertimeQuery($user);

        // Execute the query and format the results
        $overtimes = $this->formatOvertimes($query->latest()->get());

        // Prepare all props for the Inertia view
        $props = $this->preparePropsForView($overtimes);

        // Render the view
        return Inertia::render('OvertimeView', $props);
    }

    /**
     * Build the Eloquent query for fetching overtimes.
     */
    private function getOvertimeQuery($user): Builder
    {
        $query = Overtime::with(['requester:id,name,picture', 'status:id,status_name'])
            ->select('id', 'date', 'start_time', 'end_time', 'status_id', 'requester_id');

        if ($user->userType->type_name === 'employee') {
            return $query->where('requester_id', $user->id);
        }

        return $query->whereRaw('1 = 0'); // User has no overtime access
    }

    /**
     * Format the collection of material requests for the view.
     */
    private function formatOvertimes(Collection $overtimes): Collection
    {
        return $overtimes->map(function ($overtime) {
            return [
                'id' => $overtime->id,
                'start_time' => $overtime->start_time,
                'end_time' => $overtime->end_time,
                'date' => $overtime->date,
                'status' => $overtime->status->status_name,
                'requester' => [
                    'name' => $overtime->requester->name,
                    'picture' => $overtime->requester->picture
                        ? Storage::url($overtime->requester->picture)
                        : Storage::url('profile-images/default.png'),
                ],
            ];
        });
    }

    /**
     * Prepare the final props array for the Inertia component.
     */
    private function preparePropsForView(Collection $overtimes): array
    {
        return [
            'overtimes' => $overtimes,
        ];
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
        // Authorization
        $user = $request->user()->loadMissing('userType', 'employeeDetails');
        if ($user->userType->type_name !== 'employee') {
            abort(403, 'Not authorized');
        }
        
        // 1. Validation
        $request->validate([
            'date' => [
                'required', 
                'date', 
                'before_or_equal:today', 
                'after_or_equal:' . now()->subMonth()->startOfMonth()->toDateString()
            ],
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
            'reason'     => 'required|string|max:1000',
        ]);

        // 2. Compute Total Hours logic
        $startTime = Carbon::parse($request->start_time);
        $endTime = Carbon::parse($request->end_time);

        $totalMinutes = $endTime->diffInMinutes($startTime);
        $hours = floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;

        // Apply 50-minute rule
        if ($remainingMinutes > 50) {
            $hours += 1;
        }
        $hours = max($hours, 0);

        // 3. Determine Status
        $isHead = $user->employeeDetails?->is_head;
        $statusName = $isHead ? 'for approval' : 'pending';
        $statusId = Status::where('status_name', $statusName)->value('id');

        //Logic
        DB::transaction(function () use ($request, $user, $isHead, $statusId, $hours) {
            $overtime = Overtime::create([
                'requester_id' => $user->id,
                'signer_id' => $isHead ? $user->id : null,
                'status_id'    => $statusId,
                'date'         => $request->date,
                'start_time'   => $request->start_time,
                'end_time'     => $request->end_time,
                'reason'       => $request->reason,
                'total_hours'  => $hours,
            ]);

        });

        return back()->with('success', 'Overtime request submitted successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Eager load related data for efficiency
        $overtime = Overtime::with([
            'requester:id,name', 
            'signer:id,name',
            'status:id,status_name'
        ])->findOrFail($id);

        return response()->json([
            'id' => $overtime->id,
            'material' => $overtime->name,
            'date' => $overtime->date,
            'start_time' => $overtime->start_time,
            'end_time' => $overtime->end_time,
            'total_hours' => $overtime->total_hours,
            'reason' => $overtime->reason,
            'requester' => $overtime->requester->name,
            'signer' => optional($overtime->signer)->name ?? 'N/A',
            'status' => $overtime->status->status_name,
            'reject_reason' => $overtime->reject_reason
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
