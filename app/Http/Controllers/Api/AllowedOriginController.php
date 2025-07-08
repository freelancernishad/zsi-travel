<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AllowedOrigin;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AllowedOriginController extends Controller
{
    /**
     * Display a listing of the allowed origins.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Retrieve all allowed origins from the database
        $origins = AllowedOrigin::all();

        return response()->json($origins);
    }

    /**
     * Create a new allowed origin.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'origin_url' => 'required|url|unique:allowed_origins,origin_url',
        ]);

        // Create a new allowed origin
        $origin = AllowedOrigin::create([
            'origin_url' => $request->origin_url,
        ]);

        return response()->json($origin, 201);
    }

    /**
     * Update an existing allowed origin.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Validate the incoming request data
        $request->validate([
            'origin_url' => 'required|url|unique:allowed_origins,origin_url,' . $id,
        ]);

        // Find the allowed origin by ID
        $origin = AllowedOrigin::findOrFail($id);

        // Update the allowed origin
        $origin->update([
            'origin_url' => $request->origin_url,
        ]);

        return response()->json($origin);
    }

    /**
     * Delete an allowed origin.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Find the allowed origin by ID
        $origin = AllowedOrigin::findOrFail($id);

        // Delete the allowed origin
        $origin->delete();

        return response()->json(['message' => 'Origin deleted successfully.']);
    }
}
