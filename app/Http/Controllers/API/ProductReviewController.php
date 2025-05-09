<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetProductReviewsRequest;
use App\Http\Resources\RatingAndReviewResource;
use App\Models\RatingAndReview;
use App\Models\RatingsAndReviewStatistics;
// CloudFrontService might be needed if specific caching for these product-centric views is desired later.
// For now, it's not directly used by these methods after statistics are pre-calculated.
// use App\Services\CloudFrontService; 
use Illuminate\Http\JsonResponse;
// RatingsAndReviewsStatisticsService is used for READING stats, not writing, so not needed in constructor here.
// The stats are already pre-calculated.
use App\Http\Requests\GetBulkProductRatingsRequest;
use Illuminate\Support\Collection;

/**
 * Class ProductReviewController
 * 
 * Handles API requests related to reviews and rating statistics for specific products.
 */
class ProductReviewController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * Currently, this controller primarily reads pre-calculated statistics and review data.
     * Dependencies can be added here if specific services are needed for these actions.
     */
    public function __construct()
    {
        // Constructor can be used to inject services if needed in the future.
        // e.g., if CloudFront invalidation specific to these views were added.
    }

    /**
     * Display reviews for a specific product, including a pre-calculated rating summary.
     *
     * Retrieves all reviews matching the criteria and fetches pre-calculated statistics.
     * 
     * @param GetProductReviewsRequest $request The request object containing filters.
     * @param string $product_id The Product ID.
     * @return JsonResponse
     */
    public function getProductReviews(GetProductReviewsRequest $request, string $product_id): JsonResponse
    {
        // Step 1: Query for reviews based on product_id and other filters
        $query = RatingAndReview::query()->where('product_id', $product_id)->usingIndex('product_id-index');

        if ($request->has('country')) {
            $query->where('country', $request->country);
        }
        if ($request->has('language')) {
            $query->where('original_language', $request->language);
        }
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if (!$request->has('publication_status')) {
            $query->where('publication_status', 'published');
        } elseif ($request->publication_status !== 'all') {
            $query->where('publication_status', $request->publication_status);
        }
        $reviews = $query->get();

        // Step 2: Fetch pre-calculated rating statistics for the product
        $statistics = RatingsAndReviewStatistics::find($product_id);
        
        // Step 3: Prepare the rating summary, defaulting to zero/empty if no stats found
        $ratingSummary = [
            'average' => 0,
            'count' => 0,
            'distribution' => (object)array_fill_keys(range(1, 5), 0),
            'percentage_distribution' => (object)array_fill_keys(range(1, 5), 0)
        ];
        if ($statistics) {
            $ratingSummary = [
                'average' => $statistics->average_rating ?? 0,
                'count' => $statistics->rating_count ?? 0,
                'distribution' => $statistics->rating_distribution ?? (object)array_fill_keys(range(1, 5), 0),
                'percentage_distribution' => $statistics->percentage_distribution ?? (object)array_fill_keys(range(1, 5), 0)
            ];
        }

        // Step 4: Build and return the JSON response
        $response = [
            'data' => RatingAndReviewResource::collection($reviews),
            'rating_summary' => $ratingSummary
        ];
        return response()->json($response);
    }

    /**
     * Get the pre-calculated rating summary for a product.
     * 
     * Fetches statistics directly from the RatingsAndReviewStatistics model.
     * Returns a zeroed summary if no statistics are found for the product.
     * 
     * @param string $product_id Product ID.
     * @return JsonResponse
     */
    public function getProductRatingSummary(string $product_id): JsonResponse
    {
        // Step 1: Attempt to find pre-calculated statistics for the product
        $statistics = RatingsAndReviewStatistics::find($product_id);

        // Step 2: If no statistics found, return a default zeroed/empty summary
        if (!$statistics) {
            return response()->json([
                'average' => 0,
                'count' => 0,
                'distribution' => (object)array_fill_keys(range(1, 5), 0),
                'percentage_distribution' => (object)array_fill_keys(range(1, 5), 0)
            ]);
        }

        // Step 3: If statistics found, format and return them
        return response()->json([
            'average' => $statistics->average_rating ?? 0,
            'count' => $statistics->rating_count ?? 0,
            'distribution' => $statistics->rating_distribution ?? (object)array_fill_keys(range(1, 5), 0),
            'percentage_distribution' => $statistics->percentage_distribution ?? (object)array_fill_keys(range(1, 5), 0)
        ]);
    }

    /**
     * Get pre-calculated rating summaries for a list of product IDs.
     *
     * Fetches statistics directly from the RatingsAndReviewStatistics model for multiple products.
     * Returns a map of product_id to its rating summary.
     * If statistics are not found for a requested product ID, a zeroed summary is included for that ID.
     *
     * @param GetBulkProductRatingsRequest $request The validated request containing an array of product_ids.
     * @return JsonResponse A map of product IDs to their rating summaries.
     */
    public function getBulkProductRatingSummaries(GetBulkProductRatingsRequest $request): JsonResponse
    {
        $productIds = $request->validated()['product_ids'];

        $statisticsCollection = RatingsAndReviewStatistics::whereIn('product_id', $productIds)->get();
        $statsByProductId = $statisticsCollection->keyBy('product_id');

        $responseSummaries = [];
        $defaultSummaryBase = [
            'average' => 0,
            'count' => 0,
            'distribution' => (object)array_fill_keys(range(1, 5), 0),
            'percentage_distribution' => (object)array_fill_keys(range(1, 5), 0)
        ];

        foreach ($productIds as $productId) {
            if (isset($statsByProductId[$productId])) {
                $stat = $statsByProductId[$productId];
                $responseSummaries[$productId] = [
                    'product_id' => $productId,
                    'average' => $stat->average_rating ?? 0,
                    'count' => $stat->rating_count ?? 0,
                    'distribution' => $stat->rating_distribution ?? (object)array_fill_keys(range(1, 5), 0),
                    'percentage_distribution' => $stat->percentage_distribution ?? (object)array_fill_keys(range(1, 5), 0)
                ];
            } else {
                $responseSummaries[$productId] = array_merge(['product_id' => $productId], $defaultSummaryBase);
            }
        }

        return response()->json(['data' => $responseSummaries]);
    }
} 