<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

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
            if (!$user->relationLoaded('userType')) {
                $user->load('userType');
            }
            $userType = $user->userType ? $user->userType->type_name : null;
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
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'user_type' => $userType,
                ] : null,
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
