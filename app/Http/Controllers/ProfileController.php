<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var \App\Models\User $user */ 
        $user = Auth::user();

        // Load only what's needed based on user type
        $user->load('userType');
        $role = $user->userType->type_name;

        if ($role === 'employee') {
            $user->load('employeeDetails.department');
        } elseif ($role === 'intern') {
            $user->load('internDetails.department');
        }

        $pictureUrl = $user->picture
            ? Storage::url($user->picture)  // Generates full URL for stored image
            : asset('storage/profile-images/default.png');  // Fallback to default image

        // Base data common to all users
        $profileData = [
            'id' => $user->id,
            'name' => $user->name,
            'position' => $user->position,
            'address' => $user->address,
            'bday' => $user->bday,
            'gender' => $user->gender,
            'picture' => $pictureUrl
        ];

        // Add role-specific data
        if ($role === 'employee' && $user->employeeDetails) {
            $profileData['department'] = $user->employeeDetails->department->dept_name ?? null;
            $profileData['hierarchy'] = $user->employeeDetails->hierarchy;
        } elseif ($role === 'intern' && $user->internDetails) {
            $profileData['department'] = $user->internDetails->department->dept_name ?? null;
            $profileData['school'] = $user->internDetails->school;
        }

        return Inertia::render('ProfileView', [
            'profile' => $profileData
        ]);
    }

    public function updatePicture(Request $request)
    {
        $request->validate([
            'picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Delete old picture if exists
        if ($user->picture) {
            Storage::delete($user->picture);
        }

        // Store new picture
        $path = $request->file('picture')->store('profile-images', 'public');
        $user->picture = $path;
        $user->save();

        return redirect()->back()->with('success', 'Profile picture updated!');
    }

    public function deletePicture()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->picture) {
            Storage::delete($user->picture);
            $user->picture = null;
            $user->save();
        }

        return redirect()->back()->with('success', 'Profile picture removed!');
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
