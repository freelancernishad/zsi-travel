<?php

use App\Mail\TestMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\SystemSettings\SystemSettingController;

Route::get('/', function () {
    return view('welcome');
});

// For web routes
Route::get('/clear-cache', [SystemSettingController::class, 'clearCache']);


Route::get('send-test-email', function () {
    $email = 'freelancernishad123@gmail.com'; // Enter your test email here

    try {
        // Use smtp1 mailer
        $mailer = app('mail.manager')->mailer('smtp1');

        // Set the `from` dynamically
        $mailer->to($email)->send((new TestMail())->from(
            'no-reply@zsi.ai',
            'Zsi Marketing'
        ));

        return response()->json('Test email sent using smtp1!');
    } catch (\Exception $e) {
        return response()->json('Error: ' . $e->getMessage());
    }
});



Route::get('/files/{path}', function ($path) {
    try {
        // Check if the file exists in the protected disk
        if (!Storage::disk('protected')->exists($path)) {
            return response()->json([
                'error' => 'File not found',
            ], 404);
        }

        // Serve the file directly with custom headers
        return response()->file(Storage::disk('protected')->path($path))
            ->withHeaders([
                'Content-Type' => 'application/octet-stream',  // Adjust MIME type if needed
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
        ], 500);
    }
})->where('path', '.*');



