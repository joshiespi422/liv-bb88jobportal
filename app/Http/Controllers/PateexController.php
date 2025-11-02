<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PateexController extends Controller
{
    // --- CONFIGURATION ---
    private $externalApiUrl = 'https://pateex.com/api';
    private $apiToken = 'miRMjzFY23FC0XwlFQc7O5wS9pCehM34HPcebFkM6eXTJQPDfzSgadW722kuffCh';
   
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $registrantLogs = $this->fetchData($this->externalApiUrl . '/fetch_logs.php');
        $registrantsMapFilter = $this->fetchData($this->externalApiUrl . '/fetch_registrants_filter.php');
        $selectedUserId = $request->input('user', 'all');
        $registrantLocations = $this->fetchData($this->externalApiUrl . '/fetch_locations.php', ['user' => $selectedUserId]);

        return Inertia::render('PateexView', [
            'registrantLogs' => $registrantLogs,
            'registrantsMapFilter' => $registrantsMapFilter,
            'registrantLocations' => $registrantLocations,
        ]);
    }

     /**
     * A reusable private helper method to fetch data from any external endpoint.
     * @param string $url The full URL of the script to fetch.
     * @param array $queryParams Optional query parameters (like 'user').
     * @return array The decoded JSON data or an empty array on failure.
     */
    private function fetchData(string $url, array $queryParams = []): array
    {
        // Automatically add the secret token to all requests
        $allParams = array_merge(['token' => $this->apiToken], $queryParams);

        try {
            $response = Http::timeout(10) // Set a 10-second timeout
                ->get($url, $allParams);

            // Check if the request was successful
            if ($response->successful()) {
                return $response->json(); // Return the decoded JSON array
            }
            
            // Log errors if the API call fails (e.g., 403 Forbidden, 500 Error)
            Log::error('Failed to fetch external data', [
                'status' => $response->status(),
                'url' => $url,
                'params' => $queryParams,
                'body' => $response->body(),
            ]);

        } catch (\Exception $e) {
            // Log connection errors (e.g., timeout, DNS failure)
            Log::error('Exception fetching external data', [
                'message' => $e->getMessage(),
                'url' => $url,
            ]);
        }

        return []; // Return an empty array on any failure
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
