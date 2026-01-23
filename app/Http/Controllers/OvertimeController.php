<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use App\Models\Overtime;

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
        //
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
