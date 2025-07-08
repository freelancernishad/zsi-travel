<?php

namespace App\Http\Controllers\Api\Coupon;

use App\Models\User;
use App\Models\Coupon;
use App\Models\Package;
use App\Models\CouponUsage;
use Illuminate\Http\Request;
use App\Models\CouponAssociation;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    // Store a new coupon
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:coupons,code',
            'type' => 'required|string|in:percentage,flat',
            'value' => 'required|numeric|min:0',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'usage_limit' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'associations' => 'nullable|array',
            'associations.*.item_id' => 'required_with:associations|integer',
            'associations.*.item_type' => 'required_with:associations|in:user,package,service',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validated = $request->all();
        $coupon = Coupon::create($validated);

        if ($request->has('associations') && !empty($request->associations)) {
            foreach ($request->associations as $association) {
                CouponAssociation::create([
                    'coupon_id' => $coupon->id,
                    'item_id' => $association['item_id'],
                    'item_type' => $association['item_type'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Coupon created successfully',
            'coupon' => $coupon
        ], 201);
    }

    // List all coupons
    public function index()
    {
        $coupons = Coupon::with('associations')->paginate(10); // Paginated list
        return response()->json($coupons, 200);
    }

    // Edit an existing coupon
    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'string|unique:coupons,code,' . $id,
            'type' => 'string|in:percentage,flat',
            'value' => 'numeric|min:0',
            'valid_from' => 'date',
            'valid_until' => 'date|after:valid_from',
            'usage_limit' => 'integer|min:0',
            'is_active' => 'boolean',
            'associations' => 'nullable|array',
            'associations.*.item_id' => 'required_with:associations|integer',
            'associations.*.item_type' => 'required_with:associations|in:user,package,service',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $coupon->update($request->all());

        if ($request->has('associations')) {
            CouponAssociation::where('coupon_id', $id)->delete(); // Remove old associations
            foreach ($request->associations as $association) {
                CouponAssociation::create([
                    'coupon_id' => $coupon->id,
                    'item_id' => $association['item_id'],
                    'item_type' => $association['item_type'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Coupon updated successfully',
            'coupon' => $coupon
        ], 200);
    }

    // Delete a coupon
    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        CouponAssociation::where('coupon_id', $id)->delete(); // Delete related associations
        $coupon->delete();

        return response()->json([
            'message' => 'Coupon deleted successfully'
        ], 200);
    }

    // Apply coupon to a user's order
    public function apply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string|exists:coupons,code',
            'order_total' => 'required|numeric|min:0',
            'user_id' => 'nullable|exists:users,id',
            'package_id' => 'nullable|exists:packages,id',
            'service_id' => 'nullable|exists:services,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validated = $request->all();
        $coupon = Coupon::where('code', $validated['coupon_code'])->first();

        if (!$coupon || !$coupon->is_active) {
            return response()->json(['message' => 'Coupon is invalid or expired'], 400);
        }

        $now = now();
        if ($coupon->valid_from > $now || $coupon->valid_until < $now) {
            return response()->json(['message' => 'Coupon is not valid for the current date'], 400);
        }

        $validAssociation = CouponAssociation::where('coupon_id', $coupon->id)
            ->where(function ($query) use ($validated) {
                if (isset($validated['user_id'])) {
                    $query->where('item_id', $validated['user_id'])->where('item_type', 'user');
                }
                if (isset($validated['package_id'])) {
                    $query->where('item_id', $validated['package_id'])->where('item_type', 'package');
                }
                if (isset($validated['service_id'])) {
                    $query->where('item_id', $validated['service_id'])->where('item_type', 'service');
                }
            })
            ->exists();

        if (!$validAssociation) {
            return response()->json(['message' => 'Coupon is not valid for the provided item'], 400);
        }

        $discount = 0;
        if ($coupon->type === 'percentage') {
            $discount = ($validated['order_total'] * $coupon->value) / 100;
        } elseif ($coupon->type === 'flat') {
            $discount = $coupon->value;
        }

        $discounted_total = $validated['order_total'] - $discount;

        CouponUsage::create([
            'coupon_id' => $coupon->id,
            'order_total' => $validated['order_total'],
            'discount' => $discount,
        ]);

        return response()->json([
            'message' => 'Coupon applied successfully',
            'discount' => $discount,
            'discounted_total' => $discounted_total,
        ]);
    }


    public function checkCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string|exists:coupons,code',
            'user_id' => 'nullable|exists:users,id',
            'item_id' => 'nullable|integer',
            'item_type' => 'nullable|in:user,package,service',
            'product_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Fetch coupon by code
        $coupon = Coupon::where('code', $request->coupon_code)->first();

        // Check if the coupon is valid using the model's method
        if (!$coupon->isValid()) {
            return response()->json([
                'message' => 'Coupon is inactive or expired.',
                'coupon' => $coupon
            ], 400);
        }

        // Check usage limit using the model's method
        if ($coupon->hasUsageLimit()) {
            return response()->json([
                'message' => 'Coupon usage limit has been reached.',
                'coupon' => $coupon
            ], 400);
        }

        // Check if the coupon has any associations
        $hasAssociations = $coupon->associations()->exists();

        if ($hasAssociations) {
            // Validate associations if item_id and item_type are provided
            if ($request->has(['item_id', 'item_type'])) {
                $associationExists = $coupon->associations()
                    ->where('item_id', $request->item_id)
                    ->where('item_type', $request->item_type)
                    ->exists();

                if (!$associationExists) {
                    return response()->json([
                        'message' => 'Coupon is not valid for this item.',
                    ], 400);
                }
            } else {
                return response()->json([
                    'message' => 'Coupon requires an associated item.',
                ], 400);
            }
        }

        // Calculate discount using the model's method
        $productAmount = $request->product_amount;
        $discountedAmount = $coupon->getDiscountAmount($productAmount);
        $finalAmount = $productAmount - $discountedAmount;

        return response()->json([
            'message' => 'Coupon is valid.',
            'id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'product_amount' => $productAmount,
            'discount' => $coupon->type === 'percentage' ? $coupon->value . '%' : $coupon->value,
            'discounted_amount' => $discountedAmount,
            'final_amount' => $finalAmount,
        ], 200);
    }







}
