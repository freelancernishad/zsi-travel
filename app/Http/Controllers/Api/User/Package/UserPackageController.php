<?php

namespace App\Http\Controllers\Api\User\Package;

use App\Models\Coupon;
use App\Models\Package;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use App\Models\UserPackageAddon;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\CustomPackageRequest;
use Illuminate\Support\Facades\Validator;

class UserPackageController extends Controller
{
    /**
     * Get a list of packages with features and applicable discount based on duration (months).
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Get the list of all packages with features, discount rate, and discounted price
        $packages = Package::all()->makeHidden(['discounts']);

        // Return the list of packages with calculated discount details
        return response()->json($packages);
    }

    /**
     * Get a single package's details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Find the package by ID
        $package = Package::find($id)->makeHidden(['discounts']);

        if (!$package) {
            return response()->json(['message' => 'Package not found'], 404);
        }

        // Return the package details with calculated discount rate and discounted price
        return response()->json($package);
    }





    public function packagePurchase(Request $request)
    {
        // Validation rules
        $validator = Validator::make($request->all(), [
            'business_name' => 'nullable|string',
            'currency' => 'nullable|string|in:USD,EUR,GBP',
            'payable_type' => 'required|string|in:Package',
            'payable_id' => 'required|exists:packages,id',
            'addon_ids' => 'nullable|array',
            'addon_ids.*' => 'exists:package_addons,id',
            'coupon_id' => 'nullable|exists:coupons,id',
            'success_url' => 'nullable|url',
            'cancel_url' => 'nullable|url',
            'discount_months' => 'nullable|integer|min:1',
            'is_recurring' => 'nullable|boolean', // Flag for recurring payments
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Extract validated data
        $userId = auth()->id();
        $currency = $request->currency ?? 'USD';
        $business_name = $request->business_name ?? '';
        $payableType = $request->payable_type === 'Package' ? 'App\\Models\\Package' : null;
        $payableId = $request->payable_id;
        $addonIds = $request->addon_ids ?? [];
        $couponId = $request->coupon_id ?? null;
        $discountMonths = $request->discount_months ?? 0;
        $successUrl = $request->success_url ?? 'http://localhost:8000/stripe/payment/success';
        $cancelUrl = $request->cancel_url ?? 'http://localhost:8000/stripe/payment/cancel';
        $isRecurring = $request->is_recurring ?? true; // Check if recurring payment is requested

        // Retrieve package and ensure it's valid
        $package = Package::find($payableId);
        if (!$package) {
            return response()->json(['error' => 'Package not found'], 404);
        }

        // Check if the package is private
        if ($package->type === 'private') {
            $isAssigned = CustomPackageRequest::where('package_id', $package->id)
                ->where('user_id', $userId)
                ->exists();

            if (!$isAssigned) {
                return response()->json(['error' => 'You do not have permission to purchase this package.'], 403);
            }
        }

        // Get the discounted price based on the provided duration (discount_months)
        $amount = $package->getDiscountedPriceAttribute($discountMonths);

        if ($amount <= 0) {
            return response()->json(['error' => 'Payment amount must be greater than zero'], 400);
        }

        // Call createStripeCheckoutSession to handle the payment
        try {
            $paymentResult = createStripeCheckoutSession([
                'user_id' => $userId,
                'amount' => $amount,
                'currency' => $currency,
                'business_name' => $business_name,
                'payable_type' => $payableType,
                'payable_id' => $payableId,
                'addon_ids' => $addonIds,
                'coupon_id' => $couponId,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'discountMonths' => $discountMonths,
                'is_recurring' => $isRecurring, // Pass the recurring flag
            ]);

            return $paymentResult;

        } catch (\Exception $e) {
            return response()->json(['error' => 'Payment processing error: ' . $e->getMessage()], 500);
        }
    }

    function packageInvoice($id) {




        // Retrieve the UserPackage with related data
        $userPackage = UserPackage::with([
           'user',
            'package:id,name,price,features',     // Load the package relationship with specific fields
            'addons' => function ($query) {  // Limit the fields loaded for the addons
                $query->select('id', 'user_id', 'package_id', 'addon_id', 'purchase_id');
            },
            'addons.addon' => function ($query) {  // Limit the fields loaded for the addon details
                $query->select('id', 'addon_name', 'price');
            }
        ])->find($id);

        // Check if the UserPackage exists
        if (!$userPackage) {
            return response()->json(['message' => 'Package history not found'], 404);
        }

        // Hide unnecessary fields from the package
        $userPackage->package->makeHidden(['discounts', 'discounted_price']);

        $data = $userPackage;
       //  return response()->json($data);





             // Prepare the HTML content
             $htmlView = view('Invoice.invoice', compact('data'))->render();

             // Header and footer (if any, set here)
             $header = null;
             $footer = null;

             // File name
             $filename = "Invoice-$data->id.pdf";

             // Generate the PDF
             generatePdf($htmlView, $header, $footer, $filename);
   }



}
