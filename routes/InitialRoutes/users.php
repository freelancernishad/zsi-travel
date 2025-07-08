<?php


use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateUser;
use App\Http\Controllers\Api\Coupon\CouponController;
use App\Http\Controllers\Api\Auth\User\AuthUserController;
use App\Http\Controllers\Api\Auth\User\VerificationController;
use App\Http\Controllers\Api\User\Package\UserPackageController;
use App\Http\Controllers\Api\Notifications\NotificationController;
use App\Http\Controllers\Api\Auth\User\UserPasswordResetController;
use App\Http\Controllers\Api\User\UserManagement\UserProfileController;
use App\Http\Controllers\Api\User\Package\UserPurchasedHistoryController;
use App\Http\Controllers\Api\Admin\Package\CustomPackageRequestController;
use App\Http\Controllers\Api\User\SupportTicket\SupportTicketApiController;
use App\Http\Controllers\Api\User\SocialMedia\UserSocialMediaLinkController;
use App\Http\Controllers\Api\Admin\SupportTicket\AdminSupportTicketApiController;



Route::prefix('auth/user')->group(function () {
    Route::post('login', [AuthUserController::class, 'login'])->name('login');
    Route::post('register', [AuthUserController::class, 'register']);

    Route::middleware(AuthenticateUser::class)->group(function () { // Applying user middleware
        Route::post('logout', [AuthUserController::class, 'logout']);
        Route::get('me', [AuthUserController::class, 'me']);
        Route::post('change-password', [AuthUserController::class, 'changePassword']);
        Route::get('check-token', [AuthUserController::class, 'checkToken']);
    });
});

Route::prefix('user')->group(function () {
    Route::middleware(AuthenticateUser::class)->group(function () {

////// auth routes

        Route::get('/profile', [UserProfileController::class, 'getProfile']);
        Route::post('/profile', [UserProfileController::class, 'updateProfile']);



        Route::post('package/subscribe', [UserPackageController::class, 'packagePurchase']);
        Route::post('/custom/package/request', [CustomPackageRequestController::class, 'store']);

        // Support tickets
        Route::get('/support', [SupportTicketApiController::class, 'index']);
        Route::post('/support', [SupportTicketApiController::class, 'store']);
        Route::get('/support/{ticket}', [SupportTicketApiController::class, 'show']);
        Route::post('/support/{ticket}/reply', [AdminSupportTicketApiController::class, 'reply']);


        Route::get('/packages/history', [UserPurchasedHistoryController::class, 'getPurchasedHistory']);
        Route::get('/packages/history/{id}', [UserPurchasedHistoryController::class, 'getSinglePurchasedHistory']);



        // Get notifications for the authenticated user or admin
        Route::get('/notifications', [NotificationController::class, 'index']);

        // Mark a notification as read
        Route::post('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);



    });

});


Route::prefix('social-media')->group(function () {
    // Get all social media links
    Route::get('links', [UserSocialMediaLinkController::class, 'index'])->name('socialMediaLinks.index');

    // Get a specific social media link
    Route::get('links/{id}', [UserSocialMediaLinkController::class, 'show'])->name('socialMediaLinks.show');
});

Route::prefix('coupons')->group(function () {
    Route::post('/apply', [CouponController::class, 'apply']);
    Route::post('/check', [CouponController::class, 'checkCoupon']);

});


// Password reset routes
Route::post('user/password/email', [UserPasswordResetController::class, 'sendResetLinkEmail']);
Route::post('user/password/reset', [UserPasswordResetController::class, 'reset']);



Route::post('/verify-otp', [VerificationController::class, 'verifyOtp']);
Route::post('/resend/otp', [VerificationController::class, 'resendOtp']);
Route::get('/email/verify/{hash}', [VerificationController::class, 'verifyEmail']);
Route::post('/resend/verification-link', [VerificationController::class, 'resendVerificationLink']);
