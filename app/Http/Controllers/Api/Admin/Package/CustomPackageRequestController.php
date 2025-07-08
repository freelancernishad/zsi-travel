<?php

namespace App\Http\Controllers\Api\Admin\Package;

use App\Http\Controllers\Controller;
use App\Models\CustomPackageRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CustomPackageRequestController extends Controller
{
    /**
     * Display a listing of all custom package requests.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $requests = CustomPackageRequest::all();
        return response()->json([
            'success' => true,
            'data' => $requests,
        ], Response::HTTP_OK);
    }

    /**
     * Display the specified custom package request.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $request = CustomPackageRequest::find($id);

        if (!$request) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $request,
        ], Response::HTTP_OK);
    }

     /**
     * Store a newly created custom package request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate the input
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'business' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'service_description' => 'required|string',
        ]);

        // Add the authenticated user's ID to the validated data
        $validatedData['user_id'] = auth()->id();

        // Create the request
        $packageRequest = CustomPackageRequest::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Request created successfully',
            'data' => $packageRequest,
        ], Response::HTTP_CREATED);
    }



    /**
     * Update the status and admin notes for a custom package request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Validate the input
        $validatedData = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,rejected',
            'admin_notes' => 'nullable|string',
        ]);

        // Find the request
        $packageRequest = CustomPackageRequest::find($id);

        if (!$packageRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Update the request
        $packageRequest->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Request updated successfully',
            'data' => $packageRequest,
        ], Response::HTTP_OK);
    }

    /**
     * Delete a custom package request.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $request = CustomPackageRequest::find($id);

        if (!$request) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Delete the request
        $request->delete();

        return response()->json([
            'success' => true,
            'message' => 'Request deleted successfully',
        ], Response::HTTP_OK);
    }
}
