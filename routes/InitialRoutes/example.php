<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;


/**
 * Upload a file to the 'protected' disk.
 */
Route::post('/upload-file', function (Request $request) {



    $rules =  [
        'file' => 'required|file|max:2048', // Max 2MB file
    ];
    $validationResponse = validateRequest($request->all(), $rules);
    if ($validationResponse) {
        return $validationResponse; // Return if validation fails
    }


    try {
        if($request->type=='s3'){
            // Upload the file using the global function
            $filePath = uploadFileToS3($request->file('file'));
        }else{

            // Upload the file using the global function
            $filePath = uploadFileToProtected($request->file('file'));
        }

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully.',
            'file_path' => $filePath,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
        ], 400);
    }
});

/**
 * Read a file from the 'protected' disk.
 */
Route::get('/read-file/{filename}', function ($filename) {
    try {
        // Read the file using the global function
        return readFileFromProtected($filename);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
        ], 404);
    }
});

