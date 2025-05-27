<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$types): Response
    {
        /** @var \App\Models\User $user */ 
        $user = Auth::user();

        // check if user is null
        if (!$user) {  
            return redirect()->route('login');
        }

        // Eager load userType relationship if not already loaded
        if (!$user->relationLoaded('userType')) {
            $user->load('userType');
        }

        if (!$user->userType || !in_array($user->userType->type_name, $types)) {
            return abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
