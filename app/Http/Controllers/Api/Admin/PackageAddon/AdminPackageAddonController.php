<?php

namespace App\Http\Controllers\Api\Admin\PackageAddon;

use App\Http\Controllers\Controller;
use App\Models\PackageAddon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminPackageAddonController extends Controller
{
    /**
     * List all package addons.
     */
    public function index()
    {
        $addons = PackageAddon::all();
        return response()->json($addons, 200);
    }

    /**
     * Store a new package addon.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'addon_name' => 'required|string|unique:package_addons,addon_name',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $addon = PackageAddon::create($request->all());

        return response()->json([
            'message' => 'Package addon created successfully',
            'addon' => $addon
        ], 201);
    }

    /**
     * Show a specific package addon.
     */
    public function show($id)
    {
        $addon = PackageAddon::find($id);

        if (!$addon) {
            return response()->json(['message' => 'Addon not found'], 404);
        }

        return response()->json($addon, 200);
    }

    /**
     * Update a package addon.
     */
    public function update(Request $request, $id)
    {
        $addon = PackageAddon::find($id);

        if (!$addon) {
            return response()->json(['message' => 'Addon not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'addon_name' => 'sometimes|string|unique:package_addons,addon_name,' . $id,
            'price' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $addon->update($request->all());

        return response()->json([
            'message' => 'Package addon updated successfully',
            'addon' => $addon
        ], 200);
    }

    /**
     * Delete a package addon.
     */
    public function destroy($id)
    {
        $addon = PackageAddon::find($id);

        if (!$addon) {
            return response()->json(['message' => 'Addon not found'], 404);
        }

        $addon->delete();

        return response()->json(['message' => 'Package addon deleted successfully'], 200);
    }
}
