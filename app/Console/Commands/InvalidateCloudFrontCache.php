<?php

namespace App\Console\Commands;

use App\Services\CloudFrontService;
use Illuminate\Console\Command;

class InvalidateCloudFrontCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloudfront:invalidate
                            {paths?* : Specific paths to invalidate (optional)}
                            {--all : Invalidate all review media}
                            {--review= : Invalidate media for a specific review ID}
                            {--product= : Invalidate API cache for a specific product ID}
                            {--api : Invalidate all API cache}
                            {--media : Invalidate all media cache}
                            {--sync : Run synchronously instead of dispatching a job}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invalidate CloudFront cache for review media and API responses';

    /**
     * The CloudFront service instance.
     *
     * @var CloudFrontService
     */
    protected $cloudFrontService;

    /**
     * Create a new command instance.
     *
     * @param CloudFrontService $cloudFrontService
     * @return void
     */
    public function __construct(CloudFrontService $cloudFrontService)
    {
        parent::__construct();
        $this->cloudFrontService = $cloudFrontService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $paths = $this->argument('paths');
        $all = $this->option('all');
        $reviewId = $this->option('review');
        $productId = $this->option('product');
        $api = $this->option('api');
        $media = $this->option('media');
        $sync = $this->option('sync');

        // Handle all cache invalidation
        if ($all) {
            $this->info('Invalidating cache for all review media and API responses...');
            $this->cloudFrontService->invalidateAllReviewMedia();
            $this->cloudFrontService->invalidateAllReviewsApi();
            $this->info('Invalidation jobs dispatched successfully.');
            return 0;
        }

        // Handle media-only invalidation
        if ($media) {
            $this->info('Invalidating cache for all review media...');
            $this->cloudFrontService->invalidateAllReviewMedia();
            $this->info('Invalidation job dispatched successfully.');
            return 0;
        }

        // Handle API-only invalidation
        if ($api) {
            $this->info('Invalidating cache for all API responses...');
            $this->cloudFrontService->invalidateAllReviewsApi();
            $this->info('Invalidation job dispatched successfully.');
            return 0;
        }

        // Handle review-specific invalidation
        if ($reviewId) {
            $this->info("Invalidating cache for review ID: {$reviewId}...");
            $this->cloudFrontService->invalidateReviewMedia($reviewId);
            $this->cloudFrontService->invalidateReviewApi($reviewId);
            $this->info('Invalidation jobs dispatched successfully.');
            return 0;
        }

        // Handle product-specific invalidation
        if ($productId) {
            $this->info("Invalidating API cache for product ID: {$productId}...");
            $this->cloudFrontService->invalidateProductReviewsApi($productId);
            $this->info('Invalidation job dispatched successfully.');
            return 0;
        }

        // Handle specific paths
        if (!empty($paths)) {
            $this->info('Invalidating cache for specified paths...');
            $this->cloudFrontService->invalidatePaths($paths, !$sync);
            $this->info('Invalidation ' . ($sync ? 'completed' : 'job dispatched') . ' successfully.');
            return 0;
        }

        $this->error('No invalidation option specified. Use --all, --api, --media, --review=ID, --product=ID, or provide specific paths.');
        return 1;
    }
}
