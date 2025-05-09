<?php

namespace App\Services;

use App\Models\RatingAndReview; // Your main review model
use App\Models\RatingsAndReviewStatistics;
use App\Jobs\UpdateProductStatisticsJob;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Class RatingsAndReviewsStatisticsService
 * 
 * Service responsible for managing and calculating product review statistics.
 * It handles queuing of statistics updates and the core calculation logic.
 */
class RatingsAndReviewsStatisticsService
{
    /**
     * Queue a job to recalculate statistics for a given product.
     *
     * Dispatches an UpdateProductStatisticsJob to the 'statistics' queue.
     *
     * @param string $productId The ID of the product for which statistics need to be recalculated.
     * @return void
     */
    public function queueStatsRecalculation(string $productId): void
    {
        Log::info("[Service] Queueing statistics recalculation for product ID: {$productId}");
        UpdateProductStatisticsJob::dispatch($productId)->onQueue('statistics');
    }

    /**
     * Calculate and store/update review statistics for a given product.
     * 
     * This method fetches all published reviews for the specified product ID,
     * calculates the average rating, total count, and rating distribution,
     * and then saves these statistics to the `ratings_and_review_statistics` table.
     * It handles DynamoDB pagination when fetching reviews.
     * If no published reviews are found, it ensures a zeroed-out statistics record exists.
     *
     * @param string $productId The ID of the product to calculate statistics for.
     * @return bool True if the calculation and storage were successful (or if zeroed stats were appropriately set),
     *              false if an exception occurred during the process.
     */
    public function calculate(string $productId): bool
    {
        Log::info("[Service] Calculating statistics for product ID: {$productId}");

        $allRatings = [];
        $lastEvaluatedKey = null;
        $iterations = 0; 
        $maxIterations = 10000; // Safety break for unexpected loops during pagination

        try {
            // Step 1: Paginate through all published reviews for the product
            do {
                $iterations++;
                if ($iterations > $maxIterations) {
                    Log::warning("[Service] Max iterations ({$maxIterations}) reached for product ID: {$productId} during review fetching for statistics. Aborting.");
                    break;
                }

                $query = RatingAndReview::query()
                                        ->where('product_id', $productId)
                                        ->where('publication_status', 'published')
                                        ->usingIndex('product_id-index');
                
                $query->limit(100); 

                if ($lastEvaluatedKey) {
                    $query->afterKey($lastEvaluatedKey);
                }
                
                $reviewsPage = $query->get(); 
                
                if ($reviewsPage->isEmpty()) {
                    break; 
                }

                // Collect ratings from the current page
                foreach ($reviewsPage as $review) {
                    if (isset($review->rating) && is_numeric($review->rating)) {
                        $allRatings[] = (int) $review->rating;
                    }
                }
                
                // Prepare LastEvaluatedKey for the next iteration
                $lastItem = $reviewsPage->last();
                if ($lastItem) {
                    $lastEvaluatedKey = [
                        'product_id' => $lastItem->product_id, 
                        'review_id'  => $lastItem->review_id   
                    ];
                } else {
                    $lastEvaluatedKey = null; 
                }

            } while ($lastEvaluatedKey && !$reviewsPage->isEmpty() && $reviewsPage->count() === $query->getQuery()->getLimit());

            // Step 2: Handle cases with no published reviews
            if (empty($allRatings)) {
                Log::info("[Service] No published reviews found for product ID: {$productId}. Creating/updating zeroed statistics record.");
                 RatingsAndReviewStatistics::updateOrCreate(
                    ['product_id' => $productId],
                    [
                        'average_rating' => 0,
                        'rating_count' => 0,
                        'rating_distribution' => array_fill_keys(range(1, 5), 0),
                        'percentage_distribution' => array_fill_keys(range(1, 5), 0),
                        'last_calculated_at' => Carbon::now(),
                    ]
                );
                return true; 
            }

            // Step 3: Calculate statistics from the collected ratings
            $ratingCount = count($allRatings);
            $averageRating = round(array_sum($allRatings) / $ratingCount, 2);
            $ratingDistribution = array_count_values($allRatings);

            // Ensure all rating keys (1-5) exist in distribution for consistent structure
            for ($i = 1; $i <= 5; $i++) {
                if (!isset($ratingDistribution[$i])) {
                    $ratingDistribution[$i] = 0;
                }
            }
            ksort($ratingDistribution); // Sort by rating score (1-5)

            $percentageDistribution = [];
            if ($ratingCount > 0) {
                foreach ($ratingDistribution as $score => $count) {
                    $percentageDistribution[$score] = round(($count / $ratingCount) * 100, 2);
                }
            }
            ksort($percentageDistribution); // Sort by rating score (1-5)

            // Step 4: Store the calculated statistics
            RatingsAndReviewStatistics::updateOrCreate(
                ['product_id' => $productId],
                [
                    'average_rating' => $averageRating,
                    'rating_count' => $ratingCount,
                    'rating_distribution' => $ratingDistribution,
                    'percentage_distribution' => $percentageDistribution,
                    'last_calculated_at' => Carbon::now(),
                ]
            );

            Log::info("[Service] Successfully calculated and stored statistics for product ID: {$productId}");
            return true;

        } catch (\Exception $e) {
            Log::error("[Service] Error calculating statistics for product ID {$productId}: " . $e->getMessage() . " on line " . $e->getLine() . " in " . $e->getFile(), [
                'exception_class' => get_class($e),
                'trace_snippet' => substr($e->getTraceAsString(), 0, 500)
            ]);
            return false;
        }
    }
} 