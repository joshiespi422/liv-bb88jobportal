<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\Holiday;

class HolidayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('userType');

        // Build the query based on user permissions and filters
        $query = $this->getHolidayQuery($user);

        // Execute the query and format the results
        $holidays = $this->formatOvertimes($query->latest()->get());

        // Prepare all props for the Inertia view
        $props = $this->preparePropsForView($holidays);

        // Render the view
        return Inertia::render('HolidayView', $props);
    }

    /**
     * Build the Eloquent query for fetching holidays.
     */
    private function getHolidayQuery($user): Builder
    {
        $query = Holiday::query()
            ->select('id', 'date', 'name', 'type');

        if ($user->userType->type_name !== 'intern') {
            return $query;
        } 

        return $query->whereRaw('1 = 0'); // User has no overtime access
    }

    /**
     * Format the collection of overtime requests for the view.
     */
    private function formatOvertimes(Collection $holidays): Collection
    {
        return $holidays->map(function ($holiday) {
            return [
                'id' => $holiday->id,
                'name' => $holiday->name,
                'type' => $holiday->type,
                'date' => $holiday->date,
            ];
        });
    }

    /**
     * Prepare the final props array for the Inertia component.
     */
    private function preparePropsForView(Collection $holidays): array
    {
        return [
            'holidays' => $holidays,
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
        $user = $request->user()->loadMissing('userType');
        if ($user->userType->type_name !== 'super_admin') {
            abort(403, 'not authorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:regular,special',
            'date' => 'required|date|unique:holidays,date',
        ]);

        Holiday::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'date' => $validated['date'],
        ]);
        
        return back()->with('success', 'Holiday created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function destroy(Holiday $holiday)
    {
        // Authorization
        $user = Auth::user();
        $isSuperAdmin = $user->userType->type_name === 'super_admin';
        if (!$isSuperAdmin) { 
            abort(403, 'not authorized'); 
        }

        // 2. Check for approved salaries using status_name
        $hasApprovedSalaries = $holiday->salaries()
            ->whereHas('status', function ($query) {
                $query->where('status_name', 'approved');
            })
            ->exists();

        if ($hasApprovedSalaries) {
            throw ValidationException::withMessages([
                'holiday' => 'This holiday cannot be deleted because it is already linked to an approved salary.'
            ]);
        }
        $holiday->delete();

        return back()->with('success', 'Holiday deleted successfully!');
    }
}
