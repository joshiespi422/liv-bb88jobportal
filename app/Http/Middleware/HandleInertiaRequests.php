<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use Illuminate\Support\Facades\Storage;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $userType = null;

        if ($user) {
            $user->loadMissing('userType');  // Always load userType first
            $userType = $user->userType->type_name ?? null;
            $hierarchy = null;
            $department = null;

            $userType = $user->userType ? $user->userType->type_name : null;

            if ($userType === 'employee') {
                // Efficient single query with relationship constraints
                $user->loadMissing([
                    'employeeDetails' => fn($q) => $q->select('user_id', 'hierarchy', 'is_head', 'department_id'),
                    'employeeDetails.department' => fn($q) => $q->select('id', 'dept_name')
                ]);

                // Access the constrained results
                if ($user->employeeDetails) {
                    $hierarchy = $user->employeeDetails->hierarchy;
                    $department = $user->employeeDetails->department;
                }

            } elseif ($userType === 'intern') {
                // Efficient single query with relationship constraints
                $user->loadMissing([
                    'internDetails.department' => fn($q) => $q->select('id', 'dept_name')
                ]);

                // Access the constrained results
                if ($user->internDetails) {
                    $department = $user->internDetails->department;
                }
            }
        }

        return [
            ...parent::share($request),
            'ziggy' => function () use ($request) { 
                return array_merge((new Ziggy)->toArray(), [
                    'location' => $request->url(),
                    'current' => $request->route() ? $request->route()->getName() : null,
                ]);
            },
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'picture' => $user->picture
                        ? Storage::url($user->picture)
                        : Storage::url('profile-images/default.png'),
                    'userType' => $userType,
                    'hierarchy' => $hierarchy,
                    'department' => $department ? [
                        'id' => $department->id,
                        'name' => $department->dept_name
                    ] : null,
                    'isHead' => $user->employeeDetails ? $user->employeeDetails->is_head : null
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
        ];
    }
}
