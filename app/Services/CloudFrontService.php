<?php

namespace App\Services;

use App\Jobs\InvalidateCloudFrontCache;
use Illuminate\Support\Facades\Log;
// Storage facade not needed in this service

/**
 * Class CloudFrontService
 * 
 * Dispatches jobs to invalidate AWS CloudFront cache paths.
 * Ensures CDN content refreshes when underlying data changes.
 */
class CloudFrontService
{
    /**
     * Queue for CloudFront invalidation jobs
     *
     * @var string
     */
    private const INVALIDATION_QUEUE = 'cache-invalidation';

    /**
     * Invalidate CloudFront cache for a review's media files
     *
     * @param string $reviewId Review ID for media cache invalidation
     * @return void
     */
    public function invalidateReviewMedia(string $reviewId): void
    {
        $pathsToInvalidate = [
            "/reviews/{$reviewId}/*"
        ];
        Log::info("[CloudFrontService] Queuing invalidation for review media.", ['review_id' => $reviewId, 'paths' => $pathsToInvalidate]);
        InvalidateCloudFrontCache::dispatch($pathsToInvalidate)->onQueue(self::INVALIDATION_QUEUE);
    }

    /**
     * Invalidate CloudFront cache for all review media files
     *
     * @return void
     */
    public function invalidateAllReviewMedia(): void
    {
        $pathsToInvalidate = ['/reviews/*'];
        Log::info("[CloudFrontService] Queuing invalidation for all review media.", ['paths' => $pathsToInvalidate]);
        InvalidateCloudFrontCache::dispatch($pathsToInvalidate)->onQueue(self::INVALIDATION_QUEUE);
    }

    /**
     * Invalidate CloudFront cache for specific media items
     *
     * @param array<int, array{'path': string}> $mediaItems Media items with 'path' key
     * @return void
     */
    public function invalidateMediaItems(array $mediaItems): void
    {
        if (empty($mediaItems)) {
            Log::debug("[CloudFrontService] invalidateMediaItems called with no media items. No invalidation dispatched.");
            return;
        }

        $pathsToInvalidate = [];
        foreach ($mediaItems as $mediaItem) {
            if (isset($mediaItem['path']) && !empty($mediaItem['path'])) {
                // Ensure paths start with a forward slash for CloudFront invalidation
                $pathsToInvalidate[] = '/' . ltrim($mediaItem['path'], '/');
            }
        }

        if (!empty($pathsToInvalidate)) {
            Log::info("[CloudFrontService] Queuing invalidation for specific media items.", ['count' => count($pathsToInvalidate), 'paths' => $pathsToInvalidate]);
            InvalidateCloudFrontCache::dispatch($pathsToInvalidate)->onQueue(self::INVALIDATION_QUEUE);
        } else {
            Log::debug("[CloudFrontService] No valid paths derived from mediaItems. No invalidation dispatched.");
        }
    }

    /**
     * Invalidate CloudFront cache for specific paths
     *
     * @param array<int, string> $paths Paths to invalidate from CloudFront root
     * @param bool $async Whether to queue (true) or run immediately (false)
     * @return void
     */
    public function invalidatePaths(array $paths, bool $async = true): void
    {
        if (empty($paths)) {
            Log::debug("[CloudFrontService] invalidatePaths called with no paths. No invalidation dispatched.");
            return;
        }

        // Ensure all paths start with a forward slash as required by CloudFront
        $normalizedPaths = array_map(fn($path) => '/' . ltrim($path, '/'), $paths);

        $job = new InvalidateCloudFrontCache($normalizedPaths);
        Log::info("[CloudFrontService] Dispatching CloudFront invalidation.", ['path_count' => count($normalizedPaths), 'first_path_example' => $normalizedPaths[0] ?? null, 'async' => $async]);

        if ($async) {
            dispatch($job->onQueue(self::INVALIDATION_QUEUE));
        } else {
            // For synchronous dispatch, the job's handle method is called immediately.
            dispatch_sync($job);
            Log::info("[CloudFrontService] Synchronous invalidation completed.", ['path_count' => count($normalizedPaths)]);
        }
    }

    /**
     * Invalidate cache for a product's reviews API responses
     *
     * @param string $productId Product ID
     * @return void
     */
    public function invalidateProductReviewsApi(string $productId): void
    {
        $pathsToInvalidate = ["/api/products/{$productId}/reviews*"];
        try {
            Log::info("[CloudFrontService] Queuing invalidation for product reviews API.", ['product_id' => $productId, 'paths' => $pathsToInvalidate]);
            InvalidateCloudFrontCache::dispatch($pathsToInvalidate)->onQueue(self::INVALIDATION_QUEUE);
        } catch (\Exception $e) {
            // Log the error but don't throw it further to prevent breaking the calling process if dispatch fails.
            Log::error("[CloudFrontService] Failed to queue invalidation for product reviews API cache.", [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e)
            ]);
        }
    }

    /**
     * Invalidate cache for API responses related to a review
     *
     * @param string $reviewId Review ID
     * @return void
     */
    public function invalidateReviewApi(string $reviewId): void
    {
        $pathsToInvalidate = [
            "/api/reviews/{$reviewId}*",
            "/api/reviews/{$reviewId}/translate*",
            "/api/reviews/{$reviewId}/publication*",
        ];
        try {
            Log::info("[CloudFrontService] Queuing invalidation for review API.", ['review_id' => $reviewId, 'paths' => $pathsToInvalidate]);
            InvalidateCloudFrontCache::dispatch($pathsToInvalidate)->onQueue(self::INVALIDATION_QUEUE);
        } catch (\Exception $e) {
            Log::error("[CloudFrontService] Failed to queue invalidation for review API cache.", [
                'review_id' => $reviewId,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e)
            ]);
        }
    }

    /**
     * Invalidate cache for all general reviews API responses
     *
     * @return void
     */
    public function invalidateAllReviewsApi(): void
    {
        $pathsToInvalidate = [
            '/api/reviews*',
            '/api/products/*/reviews*',
        ];
        Log::info("[CloudFrontService] Queuing invalidation for all reviews API.", ['paths' => $pathsToInvalidate]);
        InvalidateCloudFrontCache::dispatch($pathsToInvalidate)->onQueue(self::INVALIDATION_QUEUE);
    }
}
