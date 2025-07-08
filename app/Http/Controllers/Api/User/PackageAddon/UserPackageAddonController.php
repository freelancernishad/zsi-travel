<?php

namespace App\Http\Controllers\Api\User\PackageAddon;

use App\Http\Controllers\Controller;
use App\Models\PackageAddon;

class UserPackageAddonController extends Controller
{
    /**
     * Get all package addons.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $addons = PackageAddon::all();
        return response()->json($addons);
    }

    /**
     * Get a single package addon by ID.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $addon = PackageAddon::find($id);

        if (!$addon) {
            return response()->json(['message' => 'Package addon not found'], 404);
        }

        return response()->json($addon);
    }
}
