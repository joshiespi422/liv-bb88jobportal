<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var \App\Models\User $user */ 
        $user = Auth::user();

        // Always load userType
        $user->load('userType');
        $role = $user->userType->type_name;

        // Conditionally load relationships
        $relations = [
            'employee' => 'employeeDetails.department',
            'intern' => 'internDetails.department'
        ];

        if ($relation = ($relations[$role] ?? null)) {
            $user->load($relation);
        }

        // Base data common to all users
        $profileData = [
            'name' => $user->name,
            'email' => $user->email,
            'qr_code' => $user->qr_code,
            'position' => $user->position,
            'address' => $user->address,
            'bday' => $user->bday,
            'gender' => $user->gender,
            'picture' => $user->picture
                ? Storage::url($user->picture)  // Generates full URL for stored image
                : Storage::url('profile-images/default.png')  // Fallback to default image
        ];

        // Dynamically add role-specific data
        $roleDetails = [
            'employee' => fn() => [
                'department' => $user->employeeDetails->department->dept_name ?? null,
                'hierarchy' => $user->employeeDetails->hierarchy ?? null
            ],
            'intern' => fn() => [
                'department' => $user->internDetails->department->dept_name ?? null,
                'school' => $user->internDetails->school ?? null
            ]
        ];

        if ($detailsHandler = ($roleDetails[$role] ?? null)) {
            $profileData = array_merge($profileData, $detailsHandler());
        }

        return Inertia::render('ProfileView', [
            'profile' => $profileData
        ]);
    }

    public function updatePicture(Request $request)
    {
        $request->validate([
            'picture' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Delete old picture if exists
        if ($user->picture) {
            Storage::disk('public')->delete($user->picture);
        }

        // Store new picture
        $path = $request->file('picture')->store('profile-images', 'public');
        $user->picture = $path;
        $user->save();

        return back()->with('success', 'Profile picture updated!');
    }

    public function deletePicture()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->picture) {
            Storage::disk('public')->delete($user->picture);
            $user->update(['picture' => null]);
        }

        return back()->with('success', 'Profile picture removed!');
    }

    public function updatePassword(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('success', 'Password updated successfully!');
    }

    public function updateDetails(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'address' => 'required|string|max:255',
            'bday' => 'required|date',
            'gender' => 'required|in:Male,Female,Other,Prefer not to say',
        ]);

        $user->update($validated);

        return back()->with('success', 'Profile details updated successfully!');
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
    public function destroy(string $id)
    {
        //
    }
}
