<?php

use App\Http\Controllers\API\RatingAndReviewController;
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

// Ratings and Reviews API Routes

//Create a review
Route::post('/reviews', [RatingAndReviewController::class, 'store']);

//admin endpoint to filter by status
Route::get('/reviews', [RatingAndReviewController::class, 'getReviewsByStatus']);
// admin endpoint - has pending reviews
Route::get('/reviews/pending-check', [RatingAndReviewController::class, 'hasPendingReviews']);

//get reviews by product id
Route::get('/products/{id}/reviews', [RatingAndReviewController::class, 'getProductReviews']);

// get the ratings of a product
Route::get('/products/{id}/rating', [RatingAndReviewController::class, 'getProductRatingSummary']);

//admin status of a review
Route::delete('/reviews/{id}', [RatingAndReviewController::class, 'destroy']);

//admin publish a review
Route::put('/reviews/{id}/publication', [RatingAndReviewController::class, 'updatePublicationStatus']);

//customer translate review
Route::get('/reviews/{id}/translate', [RatingAndReviewController::class, 'getTranslatedReview']);
