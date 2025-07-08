<?php

use Carbon\Carbon;
use App\Models\Package;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;


function getPackageRevenueData($year = null, $week = 'current')
{
    $year = $year ?? now()->year; // Default to current year

    $monthlyResult = [];
    $totalRevenueByPackage = [];
    $totalRevenueByPackageYearly = [];
    $totalRevenueByPackageWeekly = [];

    // Define the week range based on the provided $week parameter
    if ($week === 'current') {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
    } elseif ($week === 'last') {
        $weekStart = Carbon::now()->subWeek()->startOfWeek();
        $weekEnd = Carbon::now()->subWeek()->endOfWeek();
    }

    $packages = Package::all();
    foreach ($packages as $package) {
        $monthlyData = array_fill(0, 12, 0);

        // Monthly Payments
        $payments = Payment::select(
                DB::raw('MONTH(paid_at) as month'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->where('payable_type', 'Package')
            ->where('payable_id', $package->id)
            ->completed()
            ->whereYear('paid_at', $year)
            ->groupBy(DB::raw('MONTH(paid_at)'))
            ->get();

        foreach ($payments as $payment) {
            $monthlyData[$payment->month - 1] = (int) $payment->total_amount;
        }

        $totalRevenue = array_sum($monthlyData);

        $monthlyResult[] = [
            'name' => $package->name,
            'data' => $monthlyData,
        ];

        $totalRevenueByPackage[] = [
            'name' => $package->name,
            'total_revenue' => $totalRevenue,
        ];

        // Yearly Payments
        $yearlyRevenue = Payment::where('payable_type', 'Package')
            ->where('payable_id', $package->id)
            ->completed()
            ->whereYear('paid_at', $year)
            ->sum('amount');

        $totalRevenueByPackageYearly[] = [
            'name' => $package->name,
            'total_revenue_yearly' => (int) $yearlyRevenue,
        ];

        // Weekly Payments
        $weeklyData = array_fill(0, 7, 0);
        $weeklyPayments = Payment::select(
                DB::raw('DAYOFWEEK(paid_at) as day'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->where('payable_type', 'Package')
            ->where('payable_id', $package->id)
            ->completed()
            ->whereBetween('paid_at', [$weekStart, $weekEnd])
            ->groupBy(DB::raw('DAYOFWEEK(paid_at)'))
            ->get();

        foreach ($weeklyPayments as $payment) {
            $weeklyData[$payment->day - 1] = (int) $payment->total_amount;
        }

        $totalRevenueByPackageWeekly[] = [
            'name' => $package->name,
            'data' => $weeklyData,
        ];
    }

    $maxMonthlyRevenue = max(array_column($totalRevenueByPackage, 'total_revenue'));

    $maxWeeklyRevenue = 0;
    foreach ($totalRevenueByPackageWeekly as $weeklyRevenue) {
        $maxWeeklyRevenue = max($maxWeeklyRevenue, max($weeklyRevenue['data']));
    }

    return [
        'monthly_package_revenue' => $monthlyResult,
        'monthly_package_revenue_max' => getDynamicMaxValue($maxMonthlyRevenue),
        'total_revenue_per_package' => $totalRevenueByPackage,
        'yearly_package_revenue' => $totalRevenueByPackageYearly,
        'weekly_package_revenue' => $totalRevenueByPackageWeekly,
        'weekly_package_revenue_max' => getDynamicMaxValue($maxWeeklyRevenue),
    ];
}




function getDynamicMaxValue($value)
{
    // If the value is less than or equal to 0, return 0
    if ($value <= 0) {
        return 0;
    }

    // Determine the number of digits in the value
    $digitCount = strlen((string)$value);

    // Calculate the base scale dynamically based on the digit count
    $baseScale = 10 ** ($digitCount - 1); // Example: For 3 digits, baseScale = 100 (10^2)

    // For 1 and 2 digits, we set a minimum scaling factor of 100
    if ($digitCount < 3) {
        $baseScale = 100; // Minimum base scale for 1 or 2 digits
    }

    // Calculate the next max value based on the scaling factor
    $maxValue = ceil($value / $baseScale) * $baseScale;

    // Ensure the maxValue is at least the original value
    if ($maxValue < $value) {
        $maxValue += $baseScale;
    }

    return $maxValue;
}
