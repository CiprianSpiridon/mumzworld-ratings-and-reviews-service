<?php

namespace App\Services;

use App\Jobs\InvalidateCloudFrontCache;
use Illuminate\Support\Facades\Log;
// Illuminate\Support\Facades\Storage; // Storage facade is not used in this service

/**
 * Class CloudFrontService
 * 
 * Service responsible for dispatching jobs to invalidate AWS CloudFront cache paths.
 * This is used to ensure CDN content (media files, API responses) is refreshed when underlying data changes.
 */
class CloudFrontService
{
    /**
     * The queue name used for CloudFront invalidation jobs.
     *
     * @var string
     */
    private const INVALIDATION_QUEUE = 'cache-invalidation';

    /**
     * Invalidate CloudFront cache for a specific review's media files.
     *
     * Targets paths like "/reviews/{reviewId}/*".
     *
     * @param string $reviewId The ID of the review whose media cache should be invalidated.
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
     * Invalidate CloudFront cache for all review media files.
     *
     * Targets paths like "/reviews/*".
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
     * Invalidate CloudFront cache for specific media items based on their paths.
     *
     * @param array<int, array{'path': string}> $mediaItems An array of media items, each expected to have a 'path' key.
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
     * Invalidate CloudFront cache for an array of specific paths.
     *
     * @param array<int, string> $paths Array of paths to invalidate (e.g., ["/images/foo.jpg", "/api/bar"]). Paths should be absolute from the CloudFront distribution root.
     * @param bool $async Whether to dispatch the job asynchronously (queued) or synchronously.
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
     * Invalidate CloudFront cache for a specific product's reviews API responses.
     *
     * Targets paths like "/api/products/{productId}/reviews*".
     *
     * @param string $productId The ID of the product.
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
     * Invalidate CloudFront cache for API responses related to a specific review.
     *
     * Targets paths like "/api/reviews/{reviewId}*", "/api/reviews/{reviewId}/translate*", etc.
     *
     * @param string $reviewId The ID of the review.
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
     * Invalidate CloudFront cache for all general reviews API responses.
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
