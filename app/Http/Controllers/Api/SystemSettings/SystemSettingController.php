<?php

namespace App\Http\Controllers\Api\SystemSettings;

use Illuminate\Http\Request;
use App\Models\SystemSetting;
use App\Jobs\RefreshConfigCache;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

class SystemSettingController extends Controller
{
    public function storeOrUpdate(Request $request)
    {
        // Validation rules for the input
        $rules = [
            '*' => 'required|array', // Each item must be an array
            '*.key' => 'required|string', // Each key must be a string
            '*.value' => 'required|string', // Each value must be a string
        ];

        $validator = Validator::make($request->all(), $rules);

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        // Retrieve settings from the request
        $settingsData = $request->all();
        $envPath = base_path('.env');
        $envContents = file_exists($envPath) ? file_get_contents($envPath) : '';

        foreach ($settingsData as $setting) {
            // Update or create the setting in the database
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );

            // Handle AWS or other specific keys by updating the .env file
            // if (in_array($setting['key'], [
            //     'AWS_ACCESS_KEY_ID',
            //     'AWS_SECRET_ACCESS_KEY',
            //     'AWS_DEFAULT_REGION',
            //     'AWS_BUCKET',
            //     'AWS_URL',
            //     'AWS_ENDPOINT',
            //     'AWS_USE_PATH_STYLE_ENDPOINT',
            // ])) {
            //     $pattern = "/^" . preg_quote($setting['key'], '/') . "=.*/m";

            //     // Update or append the key-value pair in .env
            //     if (preg_match($pattern, $envContents)) {
            //         $envContents = preg_replace($pattern, $setting['key'] . '=' . $setting['value'], $envContents);
            //     } else {
            //         $envContents .= "\n" . $setting['key'] . '=' . $setting['value'];
            //     }
            // }
        }

        // Write back to .env safely
        if (!empty($envContents)) {
            file_put_contents($envPath, $envContents);
        }


        // Return success response
        return response()->json([
            'message' => 'System settings saved, .env updated, and configuration refreshed successfully!',
        ], 200);
    }



    public function clearCache()
    {
        try {
            // Clear and cache config
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('config:cache');

            return response()->json([
                'message' => 'Cache cleared and configuration refreshed successfully!',
            ], 200);
        } catch (\Exception $e) {
            // Log the error and return a failure response
            \Log::error('Error clearing cache: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to clear cache.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
