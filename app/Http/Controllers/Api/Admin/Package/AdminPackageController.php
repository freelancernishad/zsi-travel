<?php

namespace App\Http\Controllers\Api\Admin\Package;

use App\Models\Package;
use App\Models\PackageDiscount;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminPackageController extends Controller
{
    /**
     * Show a list of all packages.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Fetch all packages with their discounts
        $packages = Package::with('discounts')->get();

        return response()->json($packages);
    }

    /**
     * Show a single package's details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $package = Package::with('discounts')->find($id);

        if (!$package) {
            return response()->json(['message' => 'Package not found'], 404);
        }

        return response()->json($package);
    }

    /**
     * Create a new package with discounts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rules =  [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'nullable|integer|min:1',
            'features' => 'required|array',
            'discounts' => 'nullable|array',
            'discounts.*.duration_months' => 'required_with:discounts|integer|min:1',
            'discounts.*.discount_rate' => 'required_with:discounts|numeric|min:0|max:100',
        ];

        $validationResponse = validateRequest($request->all(), $rules);
        if ($validationResponse) {
            return $validationResponse; // Return if validation fails
        }

        // Create the package
        $data = $request->only(['name', 'description', 'price', 'duration_days', 'features']);
        $data['duration_days'] = 0;
        $package = Package::create($data);

        // Handle discounts
        if ($request->has('discounts')) {
            foreach ($request->discounts as $discount) {
                $package->discounts()->create($discount);
            }
        }

        return response()->json($package->load('discounts'), 201);
    }

    /**
     * Update an existing package and its discounts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $package = Package::find($id);

        if (!$package) {
            return response()->json(['message' => 'Package not found'], 404);
        }

        $rules = [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'duration_days' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
            'discounts' => 'nullable|array',
            'discounts.*.id' => 'nullable|integer|exists:package_discounts,id',
            'discounts.*.duration_months' => 'required_with:discounts|integer|min:1',
            'discounts.*.discount_rate' => 'required_with:discounts|numeric|min:0|max:100',
        ];

        $validationResponse = validateRequest($request->all(), $rules);
        if ($validationResponse) {
            return $validationResponse; // Return if validation fails
        }

        // Update package
        $data = $request->only(['name', 'description', 'price', 'duration_days', 'features']);
        $package->update(array_filter($data, fn ($value) => !is_null($value)));

        // Update discounts
        if ($request->has('discounts')) {
            foreach ($request->discounts as $discount) {
                if (isset($discount['id'])) {
                    // Update existing discount
                    $existingDiscount = PackageDiscount::find($discount['id']);
                    if ($existingDiscount) {
                        $existingDiscount->update($discount);
                    }
                } else {
                    // Create new discount
                    $package->discounts()->create($discount);
                }
            }
        }

        return response()->json($package->load('discounts'));
    }

    /**
     * Delete a package and its discounts.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $package = Package::find($id);

        if (!$package) {
            return response()->json(['message' => 'Package not found'], 404);
        }

        // Delete the package and its discounts
        $package->delete();

        return response()->json(['message' => 'Package deleted successfully']);
    }
}
