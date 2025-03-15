<?php

namespace App\Services;

use App\Models\RatingAndReview;
use Illuminate\Database\Eloquent\Collection;

class RatingCalculationService
{
    /**
     * Calculate the average rating for a product.
     *
     * @param string $productId
     * @param bool $publishedOnly Whether to include only published reviews in the calculation
     * @return array The rating statistics including average, count, and distribution
     */
    public function calculateProductRating(string $productId, bool $publishedOnly = true): array
    {
        // Start with the base query for the product
        $query = RatingAndReview::query()
            ->where('product_id', $productId)
            ->usingIndex('product_id-index');

        // Filter by publication status if needed
        if ($publishedOnly) {
            $query->where('publication_status', 'published');
        }

        // Get all reviews for the product
        $reviews = $query->get();

        // Calculate statistics using the common method
        return $this->calculateRatingStatistics($reviews);
    }

    /**
     * Calculate the percentage distribution of ratings.
     *
     * @param array $distribution The raw distribution of ratings
     * @return array The percentage distribution
     */
    public function calculatePercentageDistribution(array $distribution): array
    {
        $total = array_sum($distribution);
        $percentages = [];

        if ($total > 0) {
            foreach ($distribution as $rating => $count) {
                $percentages[$rating] = round(($count / $total) * 100, 1);
            }
        } else {
            // If no ratings, set all percentages to 0
            foreach ($distribution as $rating => $count) {
                $percentages[$rating] = 0;
            }
        }

        return $percentages;
    }

    /**
     * Calculate rating statistics from a collection of reviews.
     *
     * @param Collection $reviews Collection of RatingAndReview models
     * @return array The rating statistics including average, count, and distribution
     */
    public function calculateRatingStatistics(Collection $reviews): array
    {
        // If no reviews found, return default values
        if ($reviews->isEmpty()) {
            return [
                'average' => 0,
                'count' => 0,
                'distribution' => [
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0
                ],
                'percentage_distribution' => [
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0
                ]
            ];
        }

        // Calculate the total and count
        $total = 0;
        $count = $reviews->count();
        $distribution = [
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 => 0
        ];

        // Sum ratings and build distribution
        foreach ($reviews as $review) {
            $rating = $review->rating;
            $total += $rating;

            // Increment the count for this rating value
            if (isset($distribution[$rating])) {
                $distribution[$rating]++;
            }
        }

        // Calculate the average rating
        $average = $count > 0 ? round($total / $count, 1) : 0;

        // Calculate percentage distribution using the common method
        $percentages = $this->calculatePercentageDistribution($distribution);

        return [
            'average' => $average,
            'count' => $count,
            'distribution' => $distribution,
            'percentage_distribution' => $percentages
        ];
    }
}
