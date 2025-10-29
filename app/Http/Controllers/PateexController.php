<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PateexController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // --- CONFIGURATION ---
        $externalApiUrl = 'https://pateex.com/api/fetch_logs.php';
        $apiToken = 'miRMjzFY23FC0XwlFQc7O5wS9pCehM34HPcebFkM6eXTJQPDfzSgadW722kuffCh';

        $registrantLogs = [];

        try {
            // Make the GET request with the token as a query parameter
            $response = Http::timeout(10) // Set a 10-second timeout
                ->get($externalApiUrl, [
                    'token' => $apiToken,
                ]);

            if ($response->successful()) {
                $registrantLogs = $response->json(); // Decode the JSON response
            } else {
                Log::error('Failed to fetch external logs', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception fetching external logs', [
                'message' => $e->getMessage(),
            ]);
        }
      

        return Inertia::render('PateexView', [
            'registrantLogs' => $registrantLogs,
        ]);
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
