<?php

use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\ProductReviewController;
use App\Http\Controllers\API\ReviewTranslationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// --- Customer Facing Endpoints ---
// This group is mainly for logical separation in the file, no specific group attributes applied here.
Route::group([], function () {
    // Create a review
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');

    // Customer translate review
    Route::get('/reviews/{id}/translate', [ReviewTranslationController::class, 'getTranslatedReview'])->name('reviews.translate');

    // Get reviews for a specific product (includes rating summary)
    Route::get('/products/{product_id}/reviews', [ProductReviewController::class, 'getProductReviews'])->name('products.reviews');

    // Get the rating summary of a product
    Route::get('/products/{product_id}/rating', [ProductReviewController::class, 'getProductRatingSummary'])->name('products.rating');

    // Get rating summaries for multiple products
    Route::post('/products/ratings-summary', [ProductReviewController::class, 'getBulkProductRatingSummaries'])->name('products.ratings.bulk');
});

// --- Admin Facing Endpoints ---
// Grouped for organization and potential future shared middleware (e.g., admin auth).
// No URI prefix is applied to these routes.
Route::group(['as' => 'admin.'], function () { 
    // Admin endpoint to filter by status, user, product, etc.
    // Note: This route GET /reviews might conflict with a potential customer-facing GET /reviews if you add one later without distinct parameters or prefixes.
    // For now, assuming current structure is intended.
    Route::get('/reviews', [ReviewController::class, 'getReviewsByStatus'])->name('reviews.index');

    // Admin endpoint - check if there are pending reviews
    Route::get('/reviews/pending-check', [ReviewController::class, 'hasPendingReviews'])->name('reviews.pending_check');

    // Admin endpoint - get review counts by status (BK function)
    Route::get('/reviews/counts-by-status-bk', [ReviewController::class, 'getReviewCountsByStatusBK'])->name('reviews.counts_by_status_bk');

    // Admin endpoint - delete a review
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Admin endpoint - update publication status of a review
    Route::put('/reviews/{id}/publication', [ReviewController::class, 'updatePublicationStatus'])->name('reviews.update_publication_status');
});

