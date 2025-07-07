<?php

namespace App\Http\Controllers;

use App\Models\Accomplishment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class AccomplishmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //
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
            'user_name' => $accomplishment->user->name,
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
