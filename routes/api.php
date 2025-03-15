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
Route::post('/reviews', [RatingAndReviewController::class, 'store']);
Route::get('/products/{id}/reviews', [RatingAndReviewController::class, 'getProductReviews']);
Route::delete('/reviews/{id}', [RatingAndReviewController::class, 'destroy']);
Route::put('/reviews/{id}/publication', [RatingAndReviewController::class, 'updatePublicationStatus']);
Route::get('/reviews/{id}/translate', [RatingAndReviewController::class, 'getTranslatedReview']);
