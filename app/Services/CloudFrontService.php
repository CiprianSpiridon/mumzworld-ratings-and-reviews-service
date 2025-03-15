<?php

namespace App\Services;

use App\Jobs\InvalidateCloudFrontCache;
use Illuminate\Support\Facades\Storage;

class CloudFrontService
{
    /**
     * Invalidate CloudFront cache for a specific review's media files.
     *
     * @param string $reviewId
     * @return void
     */
    public function invalidateReviewMedia(string $reviewId)
    {
        // Dispatch job to invalidate the specific review's media files
        InvalidateCloudFrontCache::dispatch([
            "/reviews/{$reviewId}/*",
        ])->onQueue('cache-invalidation');
    }

    /**
     * Invalidate CloudFront cache for all review media files.
     *
     * @return void
     */
    public function invalidateAllReviewMedia()
    {
        // Dispatch job to invalidate all review media files
        InvalidateCloudFrontCache::dispatch([
            '/reviews/*',
        ])->onQueue('cache-invalidation');
    }

    /**
     * Invalidate CloudFront cache for specific media files.
     *
     * @param array $mediaItems Array of media items with 'path' key
     * @return void
     */
    public function invalidateMediaItems(array $mediaItems)
    {
        if (empty($mediaItems)) {
            return;
        }

        $paths = [];
        foreach ($mediaItems as $mediaItem) {
            if (isset($mediaItem['path'])) {
                $paths[] = '/' . ltrim($mediaItem['path'], '/');
            }
        }

        if (!empty($paths)) {
            InvalidateCloudFrontCache::dispatch($paths)->onQueue('cache-invalidation');
        }
    }

    /**
     * Invalidate CloudFront cache for specific paths.
     *
     * @param array $paths Array of paths to invalidate
     * @param bool $async Whether to dispatch the job asynchronously
     * @return void
     */
    public function invalidatePaths(array $paths, bool $async = true)
    {
        if (empty($paths)) {
            return;
        }

        $job = new InvalidateCloudFrontCache($paths);

        if ($async) {
            dispatch($job->onQueue('cache-invalidation'));
        } else {
            dispatch_sync($job);
        }
    }

    /**
     * Invalidate CloudFront cache for a specific product's reviews API responses.
     *
     * @param string $productId
     * @return void
     */
    public function invalidateProductReviewsApi(string $productId)
    {
        // Invalidate all API responses for this product's reviews
        InvalidateCloudFrontCache::dispatch([
            "/api/products/{$productId}/reviews*",
        ])->onQueue('cache-invalidation');
    }

    /**
     * Invalidate CloudFront cache for a specific review's API responses.
     *
     * @param string $reviewId
     * @return void
     */
    public function invalidateReviewApi(string $reviewId)
    {
        // Invalidate all API responses for this review
        InvalidateCloudFrontCache::dispatch([
            "/api/reviews/{$reviewId}*",
            "/api/reviews/{$reviewId}/translate*",
            "/api/reviews/{$reviewId}/publication*",
        ])->onQueue('cache-invalidation');
    }

    /**
     * Invalidate CloudFront cache for all reviews API responses.
     *
     * @return void
     */
    public function invalidateAllReviewsApi()
    {
        // Invalidate all reviews API responses
        InvalidateCloudFrontCache::dispatch([
            '/api/reviews*',
            '/api/products/*/reviews*',
        ])->onQueue('cache-invalidation');
    }
}
