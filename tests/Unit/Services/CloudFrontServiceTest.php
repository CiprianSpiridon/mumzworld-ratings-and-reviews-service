<?php

namespace Tests\Unit\Services;

use App\Jobs\InvalidateCloudFrontCache;
use App\Services\CloudFrontService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CloudFrontServiceTest extends TestCase
{
    protected CloudFrontService $cloudFrontService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cloudFrontService = new CloudFrontService();
        Queue::fake();
    }

    public function testInvalidateReviewMedia()
    {
        $reviewId = 'test-review-id';

        $this->cloudFrontService->invalidateReviewMedia($reviewId);

        Queue::assertPushed(InvalidateCloudFrontCache::class, function ($job) use ($reviewId) {
            return $job->paths === ["/reviews/{$reviewId}/*"];
        });
    }

    public function testInvalidateAllReviewMedia()
    {
        $this->cloudFrontService->invalidateAllReviewMedia();

        Queue::assertPushed(InvalidateCloudFrontCache::class, function ($job) {
            return $job->paths === ['/reviews/*'];
        });
    }

    public function testInvalidateMediaItems()
    {
        $mediaItems = [
            ['id' => '1', 'path' => 'reviews/123/image1.jpg'],
            ['id' => '2', 'path' => '/reviews/123/image2.jpg'],
        ];

        $this->cloudFrontService->invalidateMediaItems($mediaItems);

        Queue::assertPushed(InvalidateCloudFrontCache::class, function ($job) {
            return $job->paths === ['/reviews/123/image1.jpg', '/reviews/123/image2.jpg'];
        });
    }

    public function testInvalidateMediaItemsWithEmptyArray()
    {
        $this->cloudFrontService->invalidateMediaItems([]);

        Queue::assertNotPushed(InvalidateCloudFrontCache::class);
    }

    public function testInvalidateMediaItemsWithInvalidItems()
    {
        $mediaItems = [
            ['id' => '1'], // Missing path
            ['url' => 'https://example.com/image.jpg'], // Missing path
        ];

        $this->cloudFrontService->invalidateMediaItems($mediaItems);

        Queue::assertNotPushed(InvalidateCloudFrontCache::class);
    }

    public function testInvalidatePathsAsync()
    {
        $paths = ['/path1', '/path2'];

        $this->cloudFrontService->invalidatePaths($paths);

        Queue::assertPushed(InvalidateCloudFrontCache::class, function ($job) use ($paths) {
            return $job->paths === $paths;
        });
    }

    public function testInvalidatePathsSync()
    {
        $paths = ['/path1', '/path2'];

        $this->cloudFrontService->invalidatePaths($paths, false);

        // For sync jobs, we'd need to mock the handle method, but for this test
        // we're just verifying it doesn't get pushed to the queue
        Queue::assertNotPushed(InvalidateCloudFrontCache::class);
    }

    public function testInvalidatePathsWithEmptyArray()
    {
        $this->cloudFrontService->invalidatePaths([]);

        Queue::assertNotPushed(InvalidateCloudFrontCache::class);
    }

    public function testInvalidateProductReviewsApi()
    {
        $productId = 'test-product-id';

        $this->cloudFrontService->invalidateProductReviewsApi($productId);

        Queue::assertPushed(InvalidateCloudFrontCache::class, function ($job) use ($productId) {
            return $job->paths === ["/api/products/{$productId}/reviews*"];
        });
    }

    public function testInvalidateReviewApi()
    {
        $reviewId = 'test-review-id';

        $this->cloudFrontService->invalidateReviewApi($reviewId);

        Queue::assertPushed(InvalidateCloudFrontCache::class, function ($job) use ($reviewId) {
            return $job->paths === [
                "/api/reviews/{$reviewId}*",
                "/api/reviews/{$reviewId}/translate*",
                "/api/reviews/{$reviewId}/publication*",
            ];
        });
    }

    public function testInvalidateAllReviewsApi()
    {
        $this->cloudFrontService->invalidateAllReviewsApi();

        Queue::assertPushed(InvalidateCloudFrontCache::class, function ($job) {
            return $job->paths === [
                '/api/reviews*',
                '/api/products/*/reviews*',
            ];
        });
    }
}
