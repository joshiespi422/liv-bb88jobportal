<?php

namespace App\Http\Controllers;

use App\Models\Accomplishment;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AccomplishmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('userType', 'employeeDetails', 'internDetails');

        // Determine key parameters based on user role
        ['accomplishmentType' => $accomplishmentType, 'currentDepartmentId' => $currentDepartmentId] = $this->determineParameters($request, $user);

        // Determine which tab should be active ('own' or 'all')
        $activeTab = $this->getActiveTab($request, $user, $accomplishmentType);

        // Build the query to fetch accomplishments
        $query = $this->getAccomplishmentsQuery($user, $activeTab, $accomplishmentType, $currentDepartmentId);

        // Execute the query and format the results for the view
        $accomplishments = $this->formatAccomplishments($query->get());

        // Render the Inertia view with all necessary props
        return Inertia::render('AccomplishmentView', [
            'accomplishments' => $accomplishments,
            'departments' => Department::all(['id', 'dept_name']) ?? [],
            'currentDepartmentId' => $currentDepartmentId ? (int)$currentDepartmentId : null,
            'currentType' => $accomplishmentType,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Determines the accomplishment type and department ID based on the user's role and request parameters.
     */
    private function determineParameters(Request $request, $user): array
    {
        $userType = $user->userType->type_name;

        switch (true) {
            // SUPER ADMIN: Can view any department and type, 
            case $userType === 'super_admin':
                $accomplishmentType = in_array($request->type, ['employee', 'intern']) ? $request->type : 'employee';
                // get the dept id from url or session, default to 1st, and store in session
                $departmentId = $request->dept ?? session('current_department_id', Department::orderBy('id')->first()?->id); 
                session(['current_department_id' => $departmentId]);
                return ['accomplishmentType' => $accomplishmentType, 'currentDepartmentId' => $departmentId];

            // EMPLOYEE LEADER: Can view their department's employees or interns
            case $userType === 'employee' && $user->employeeDetails?->hierarchy === 'Leader':
                $accomplishmentType = in_array($request->type, ['employee', 'intern']) ? $request->type : 'employee';
                // If intern, allow session/request; otherwise, force their own department
                $departmentId = ($accomplishmentType === 'intern') 
                    ? ($request->dept ?? session('current_department_id', $user->employeeDetails->department_id))
                    : $user->employeeDetails->department_id;
                // Only persist to session if they are allowed to toggle (intern mode)
                if ($accomplishmentType === 'intern') session(['current_department_id' => $departmentId]);
                return ['accomplishmentType' => $accomplishmentType, 'currentDepartmentId' => $departmentId];

            // REGULAR EMPLOYEE / INTERN: Can only view their own
            default:
                $accomplishmentType = ($userType === 'intern') ? 'intern' : 'employee';
                $departmentId = ($userType === 'employee') ? $user->employeeDetails?->department_id : $user->internDetails?->department_id;
                return ['accomplishmentType' => $accomplishmentType, 'currentDepartmentId' => $departmentId];
        }
    }

    /**
     * Determines the active tab based on user role and context.
     */
    private function getActiveTab(Request $request, $user, string $accomplishmentType): string
    {
        // Define the default tab
        $isLeaderViewingInterns = $user->userType->type_name === 'employee'
            && $user->employeeDetails?->hierarchy === 'Leader'
            && $accomplishmentType === 'intern';

        $defaultTab = $isLeaderViewingInterns || $user->userType->type_name === 'super_admin' ? 'all' : 'own';

        // Return the requested tab if valid, otherwise return the default
        return in_array($request->tab, ['own', 'all']) ? $request->tab : $defaultTab;
    }

    /**
     * Builds the Eloquent query for fetching accomplishments with appropriate filters.
     */
    private function getAccomplishmentsQuery($user, string $activeTab, string $accomplishmentType, ?int $departmentId): Builder
    {
        $query = Accomplishment::with(['user:id,name,picture', 'tasks:id,title'])
                               ->orderBy('created_at', 'desc');

        // Filter for 'own' tab: show only the logged-in user's accomplishments
        if ($activeTab === 'own' && $user->userType->type_name === $accomplishmentType) {
            return $query->where('user_id', $user->id);
        }

        // Filter for 'all' tab: show accomplishments from a specific department
        $relation = $accomplishmentType === 'employee' ? 'user.employeeDetails' : 'user.internDetails';

        return $query->whereHas($relation, function ($q) use ($departmentId) {
            $q->where('department_id', $departmentId);
        });
    }

    /**
     * Formats the collection of accomplishments for the Inertia view.
     */
    private function formatAccomplishments(Collection $accomplishments): Collection
    {
        return $accomplishments->map(function ($accomplishment) {
            return [
                'id' => $accomplishment->id,
                'title' => $accomplishment->title,
                'task_title' => $accomplishment->tasks->first()?->title,
                'created_at' => $accomplishment->created_at,
                'user' => [
                    'name' => $accomplishment->user->name,
                    'picture' => $accomplishment->user->picture,
                ],
            ];
        });
    }

    /**
     * Export specific accomplishments by their IDs.
     */
    public function export(Request $request)
    {
        // 1. Validate the incoming request to ensure 'ids' is an array
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:accomplishments,id', // Ensure all IDs exist
        ]);

        $accomplishmentIds = $validated['ids'];

        // 2. Fetch only the requested accomplishments with their relations
        $accomplishments = Accomplishment::with(['user:id,name', 'tasks:id,title'])
            ->whereIn('id', $accomplishmentIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($accomplishment) {
                // 3. Map to the desired JSON structure for the exporter
                return [
                    'user_name' => $accomplishment->user->name,
                    'project_name' => optional($accomplishment->tasks->first())->title,
                    'accomplish_title' => $accomplishment->title,
                    'date_report' => Carbon::parse($accomplishment->created_at)->format('M j, Y, g:i a'),
                    'description' => $accomplishment->description,
                    'link' => $accomplishment->link,
                    'attachment_url' => $accomplishment->attachment ? asset(Storage::url($accomplishment->attachment)) : null
                ];
            });

        return response()->json($accomplishments);
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
        $accomplishment = Accomplishment::with([
                'user:id,name',
                'tasks:id,title'
            ])
            ->findOrFail($id);

        return response()->json([
            'id' => $accomplishment->id,
            'title' => $accomplishment->title,
            'description' => $accomplishment->description,
            'link' => $accomplishment->link,
            'attachment' => $accomplishment->attachment 
                ? [
                    'url' => Storage::url($accomplishment->attachment),
                    'name' => basename($accomplishment->attachment)
                ]
                : null,
            'created_at' => $accomplishment->created_at,
            'user' => [
                'id' => $accomplishment->user->id,
                'name' => $accomplishment->user->name
            ],            
            'task_title' => $accomplishment->tasks->first()->title, 
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
    public function update(Request $request, Accomplishment $accomplishment)
    {
        // 1. Authorization - User must own the accomplishment
        if ($accomplishment->user_id !== $request->user()->id) {
            abort(403, 'not authorized');
        }

        // 2. Validate edit timeframe (same day only)
        $createdDate = $accomplishment->created_at->format('Y-m-d');
        $today = Carbon::now()->format('Y-m-d');
        
        if ($createdDate !== $today) {
            abort(403, 'editable only on the same day');
        }

        // 3. Validate input
        $request->validate([
            'description' => 'required|string|max:1000',
            'link' => 'nullable|url|max:255',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,docx,doc|max:5120',
        ]);

        // 4. Handle attachment update
        $attachmentPath = $accomplishment->attachment;
        
        if ($request->hasFile('attachment')) {
            // Delete old attachment if exists
            if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
                Storage::disk('public')->delete($attachmentPath);
            }
            
            // Store new attachment
            $attachmentPath = $request->file('attachment')
                ->store('accomplishment-files', 'public');
        }

        // 5. Update accomplishment
        $accomplishment->update([
            'description' => $request->description,
            'link' => $request->link,
            'attachment' => $attachmentPath,
        ]);

        return back()->with('success', 'Accomplishment updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
