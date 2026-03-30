<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Models\HalfDay;
use ParagonIE\Sodium\Core\Curve25519\H;

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
