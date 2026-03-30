<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Models\HalfDay;
use App\Models\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HalfDayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('userType', 'employeeDetails.department');

        // Build the query based on user permissions and filters
        $query = $this->getHalfDaysQuery($user);

        // Execute the query and format the results
        $halfDays = $this->formatHalfDays($query->latest()->get());

        // Prepare all props for the Inertia view
        $props = $this->preparePropsForView($halfDays);

        // Render the view
        return Inertia::render('HalfDayView', $props);
    }

    /**
     * Build the Eloquent query for fetching half days.
     */
    private function getHalfDaysQuery($user): Builder
    {
        $query = HalfDay::with(['requester:id,name,picture', 'status:id,status_name'])
            ->select('id', 'date', 'shift', 'status_id', 'requester_id');

        if ($user->userType->type_name === 'employee') {
            $departmentId = $user->employeeDetails?->department_id;

            // If the user doesn't have a department, they shouldn't see anything
            if (!$departmentId) {
                return $query->whereRaw('1 = 0');
            }

            // Filter half days where the requester belongs to the same department
            return $query->whereHas('requester.employeeDetails', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        } elseif ($user->userType->type_name === 'super_admin') {
            return $query;
        }

        return $query->whereRaw('1 = 0'); // User has no half day access
    }

    /**
     * Format the collection of half day requests for the view.
     */
    private function formatHalfDays(Collection $halfDays): Collection
    {
        return $halfDays->map(function ($halfDay) {
            return [
                'id' => $halfDay->id,
                'shift' => $halfDay->shift,
                'date' => $halfDay->date,
                'status' => $halfDay->status->status_name,
                'requester' => [
                    'name' => $halfDay->requester->name,
                    'picture' => $halfDay->requester->picture,
                ],
            ];
        });
    }

    /**
     * Prepare the final props array for the Inertia component.
     */
    private function preparePropsForView(Collection $halfDays): array
    {
        return [
            'halfDays' => $halfDays,
        ];
    }

    public function signHalfDay(HalfDay $halfDay)
    {
        // Authorization
        $user = Auth::user();
        $isHead = $user->employeeDetails?->is_head;
        $isSuperAdmin = $user->userType->type_name === 'super_admin';
        if (!$isHead || $isSuperAdmin) { 
            abort(403, 'not authorized'); 
        }

        $halfDay->loadMissing([
            'status:id,status_name',
        ]);

        if ($halfDay->status->status_name !== 'pending') {
            abort(403, 'half day request is not pending');
        }

        // Logic
        DB::transaction(function () use ($halfDay, $user) {
            $forApprovalStatusId = Status::where('status_name', 'for approval')->value('id');
            $halfDay->update([
                'status_id' => $forApprovalStatusId,
                'signer_id' => $user->id
            ]);

            // Dispatch event
            // OvertimeSigned::dispatch($halfDay);
        });
        
        return back()->with('success', 'Half day signed successfully!');
    }
    
    public function validateHalfDay(Request $request, HalfDay $halfDay)
    {
        // Authorization
        $user = Auth::user();
        $isSuperAdmin = $user->userType->type_name === 'super_admin';
        if (!$isSuperAdmin) { 
            abort(403, 'not authorized'); 
        }

        $halfDay->loadMissing([
            'status:id,status_name',
        ]);
        
        if ($halfDay->status->status_name !== 'for approval') {
            abort(403, 'half day request is not for approval');
        }

        // Validation
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'reject_reason' => 'nullable|required_if:status,rejected|string|max:1000',
        ]);

        // Logic
        DB::transaction(function () use ($request, $halfDay) {
            $newStatus = Status::where('status_name', $request->status)->firstOrFail();
            $halfDay->status_id = $newStatus->id;

            // If status is 'rejected', save the reason. Otherwise, clear it.
            if ($request->status === 'rejected') {
                $halfDay->reject_reason = $request->reject_reason;
            } else {
                $halfDay->reject_reason = null;
            }

            $halfDay->save();

            // Dispatch event
            // OvertimeValidated::dispatch($halfDay);
        });
        
        return back()->with('success', 'Half day request validated successfully!');
    }

    public function store(Request $request)
    {
        // Authorization
        $user = $request->user()->loadMissing('userType', 'employeeDetails');
        if ($user->userType->type_name !== 'employee') {
            abort(403, 'Not authorized');
        }
        
        // Validation
        $request->validate([
            'date' => [
                'required', 
                'date', 
                'before_or_equal:today', 
                'after_or_equal:' . now()->subMonth()->startOfMonth()->toDateString()
            ],
            'shift' => 'required|in:morning,afternoon',
            'reason'     => 'required|string|max:1000',
        ]);

        $isHead = $user->employeeDetails?->is_head;
        $statusName = $isHead ? 'for approval' : 'pending';
        $statusId = Status::where('status_name', $statusName)->value('id');

        //Logic
        DB::transaction(function () use ($request, $user, $isHead, $statusId) {
            $halfDay = HalfDay::create([
                'requester_id' => $user->id,
                'signer_id' => $isHead ? $user->id : null,
                'status_id'    => $statusId,
                'date'         => $request->date,
                'shift'   => $request->shift,
                'reason'       => $request->reason,
            ]);

            // Dispatch notification
            // OvertimeCreated::dispatch($halfDay);
        });

        return back()->with('success', 'Half day request submitted successfully');
    }

    public function show(string $id)
    {
        // Eager load related data for efficiency
        $halfDay = HalfDay::with([
            'requester:id,name', 
            'signer:id,name',
            'status:id,status_name'
        ])->findOrFail($id);

        return response()->json([
            'id' => $halfDay->id,
            'date' => $halfDay->date,
            'shift' => $halfDay->shift,
            'reason' => $halfDay->reason,
            'requester' => $halfDay->requester->name,
            'signer' => optional($halfDay->signer)->name ?? 'N/A',
            'status' => $halfDay->status->status_name,
            'reject_reason' => $halfDay->reject_reason
        ]);
    }
}
