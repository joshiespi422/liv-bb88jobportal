<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
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
        $user = $request->user()->loadMissing('userType', 'employeeDetails.department');

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
            $departmentId = $user->employeeDetails?->department_id;

            // If the user doesn't have a department, they shouldn't see anything
            if (!$departmentId) {
                return $query->whereRaw('1 = 0');
            }

            // Filter overtimes where the requester belongs to the same department
            return $query->whereHas('requester.employeeDetails', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
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

    public function signOvertime(Overtime $overtime)
    {
        // Authorization
        $user = Auth::user();
        $isHead = $user->employeeDetails?->is_head;
        $isSuperAdmin = $user->userType->type_name === 'super_admin';
        if (!$isHead || $isSuperAdmin) { 
            abort(403, 'not authorized'); 
        }

        $overtime->loadMissing([
            'status:id,status_name',
        ]);

        if ($overtime->status->status_name !== 'pending') {
            abort(403, 'material request is not pending');
        }

        // Logic
        DB::transaction(function () use ($overtime, $user) {
            $forApprovalStatusId = Status::where('status_name', 'for approval')->value('id');
            $overtime->update([
                'status_id' => $forApprovalStatusId,
                'signer_id' => $user->id
            ]);
        });
        
        return back()->with('success', 'Overtime signed successfully!');
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
            'end_time' => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($request) {
                    // Convert "18:00" to minutes: (18 * 60) + 0 = 1080
                    $startParts = explode(':', $request->start_time);
                    $endParts = explode(':', $value);

                    $startMinutes = ($startParts[0] * 60) + $startParts[1];
                    $endMinutes = ($endParts[0] * 60) + $endParts[1];
                    $diff = $endMinutes - $startMinutes;

                    if ($diff <= 0) {
                        $fail('The end time must be after the start time.');
                        return;
                    }

                    if ($diff < 50) {
                        $fail('The overtime duration must be at least 50 minutes.');
                    }
                },
            ],
            'reason'     => 'required|string|max:1000',
        ]);

        // 2. Compute Total Hours logic (Raw Math)
        $startParts = explode(':', $request->start_time);
        $endParts = explode(':', $request->end_time);

        $startMinutes = ($startParts[0] * 60) + $startParts[1];
        $endMinutes = ($endParts[0] * 60) + $endParts[1];
        $totalMinutes = $endMinutes - $startMinutes;

        $baseHours = floor($totalMinutes / 60);
        $remainingMinutes = $totalMinutes % 60;

        // Apply "50 minutes and above consider it 1hr"
        $computedHours = ($remainingMinutes >= 50) ? ($baseHours + 1) : $baseHours;
        // Safety check: ensure we record at least 1 hour if it passed validation
        $finalHours = max(1, (int) $computedHours);

        // 3. Determine Status
        $isHead = $user->employeeDetails?->is_head;
        $statusName = $isHead ? 'for approval' : 'pending';
        $statusId = Status::where('status_name', $statusName)->value('id');

        //Logic
        DB::transaction(function () use ($request, $user, $isHead, $statusId, $finalHours) {
            $overtime = Overtime::create([
                'requester_id' => $user->id,
                'signer_id' => $isHead ? $user->id : null,
                'status_id'    => $statusId,
                'date'         => $request->date,
                'start_time'   => $request->start_time,
                'end_time'     => $request->end_time,
                'reason'       => $request->reason,
                'total_hours'  => $finalHours,
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
