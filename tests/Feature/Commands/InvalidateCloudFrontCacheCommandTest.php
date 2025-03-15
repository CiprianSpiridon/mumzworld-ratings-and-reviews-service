<?php

namespace Tests\Feature\Commands;

use App\Services\CloudFrontService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class InvalidateCloudFrontCacheCommandTest extends TestCase
{
    public function testInvalidateAll()
    {
        // Mock the CloudFrontService
        $mockService = Mockery::mock(CloudFrontService::class);
        $mockService->shouldReceive('invalidateAllReviewMedia')->once();
        $mockService->shouldReceive('invalidateAllReviewsApi')->once();

        $this->app->instance(CloudFrontService::class, $mockService);

        // Run the command
        $this->artisan('cloudfront:invalidate', ['--all' => true])
            ->expectsOutput('Invalidating cache for all review media and API responses...')
            ->expectsOutput('Invalidation jobs dispatched successfully.')
            ->assertExitCode(0);
    }

    public function testInvalidateMedia()
    {
        // Mock the CloudFrontService
        $mockService = Mockery::mock(CloudFrontService::class);
        $mockService->shouldReceive('invalidateAllReviewMedia')->once();

        $this->app->instance(CloudFrontService::class, $mockService);

        // Run the command
        $this->artisan('cloudfront:invalidate', ['--media' => true])
            ->expectsOutput('Invalidating cache for all review media...')
            ->expectsOutput('Invalidation job dispatched successfully.')
            ->assertExitCode(0);
    }

    public function testInvalidateApi()
    {
        // Mock the CloudFrontService
        $mockService = Mockery::mock(CloudFrontService::class);
        $mockService->shouldReceive('invalidateAllReviewsApi')->once();

        $this->app->instance(CloudFrontService::class, $mockService);

        // Run the command
        $this->artisan('cloudfront:invalidate', ['--api' => true])
            ->expectsOutput('Invalidating cache for all API responses...')
            ->expectsOutput('Invalidation job dispatched successfully.')
            ->assertExitCode(0);
    }

    public function testInvalidateReview()
    {
        $reviewId = 'test-review-id';

        // Mock the CloudFrontService
        $mockService = Mockery::mock(CloudFrontService::class);
        $mockService->shouldReceive('invalidateReviewMedia')->once()->with($reviewId);
        $mockService->shouldReceive('invalidateReviewApi')->once()->with($reviewId);

        $this->app->instance(CloudFrontService::class, $mockService);

        // Run the command
        $this->artisan('cloudfront:invalidate', ['--review' => $reviewId])
            ->expectsOutput("Invalidating cache for review ID: {$reviewId}...")
            ->expectsOutput('Invalidation jobs dispatched successfully.')
            ->assertExitCode(0);
    }

    public function testInvalidateProduct()
    {
        $productId = 'test-product-id';

        // Mock the CloudFrontService
        $mockService = Mockery::mock(CloudFrontService::class);
        $mockService->shouldReceive('invalidateProductReviewsApi')->once()->with($productId);

        $this->app->instance(CloudFrontService::class, $mockService);

        // Run the command
        $this->artisan('cloudfront:invalidate', ['--product' => $productId])
            ->expectsOutput("Invalidating API cache for product ID: {$productId}...")
            ->expectsOutput('Invalidation job dispatched successfully.')
            ->assertExitCode(0);
    }

    public function testInvalidatePaths()
    {
        $paths = ['/path1', '/path2'];

        // Mock the CloudFrontService
        $mockService = Mockery::mock(CloudFrontService::class);
        $mockService->shouldReceive('invalidatePaths')->once()->with($paths, true);

        $this->app->instance(CloudFrontService::class, $mockService);

        // Run the command
        $this->artisan('cloudfront:invalidate', ['paths' => $paths])
            ->expectsOutput('Invalidating cache for specified paths...')
            ->expectsOutput('Invalidation job dispatched successfully.')
            ->assertExitCode(0);
    }

    public function testInvalidatePathsSync()
    {
        $paths = ['/path1', '/path2'];

        // Mock the CloudFrontService
        $mockService = Mockery::mock(CloudFrontService::class);
        $mockService->shouldReceive('invalidatePaths')->once()->with($paths, false);

        $this->app->instance(CloudFrontService::class, $mockService);

        // Run the command
        $this->artisan('cloudfront:invalidate', ['paths' => $paths, '--sync' => true])
            ->expectsOutput('Invalidating cache for specified paths...')
            ->expectsOutput('Invalidation completed successfully.')
            ->assertExitCode(0);
    }

    public function testNoOptions()
    {
        // Run the command with no options
        $this->artisan('cloudfront:invalidate')
            ->expectsOutput('No invalidation option specified. Use --all, --api, --media, --review=ID, --product=ID, or provide specific paths.')
            ->assertExitCode(1);
    }
}
