<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Aws\CloudFront\CloudFrontClient;
use Aws\Exception\AwsException;

/**
 * Invalidates CloudFront cache for specified paths
 */
class InvalidateCloudFrontCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Paths to invalidate
     *
     * @var array
     */
    protected array $paths;

    /**
     * CloudFront distribution ID
     *
     * @var string
     */
    protected string $distributionId;

    /**
     * Maximum retry attempts
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Retry backoff in seconds
     *
     * @var array
     */
    public $backoff = [30, 60, 120];

    /**
     * Create a new job instance
     *
     * @param array $paths Paths to invalidate
     * @param string|null $distributionId Optional, defaults to config
     * @return void
     */
    public function __construct(array $paths, ?string $distributionId = null)
    {
        $this->paths = $paths;
        $this->distributionId = $distributionId ?? config('services.cloudfront.distribution_id');
        $this->queue = 'cache-invalidation';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (empty($this->distributionId)) {
            Log::warning('CloudFront invalidation skipped: No distribution ID provided');
            return;
        }

        if (empty($this->paths)) {
            Log::warning('CloudFront invalidation skipped: No paths provided');
            return;
        }

        try {
            $cloudFrontClient = $this->createCloudFrontClient();

            // Format paths for CloudFront (must start with /)
            $formattedPaths = array_map(function ($path) {
                return strpos($path, '/') === 0 ? $path : '/' . $path;
            }, $this->paths);

            $result = $cloudFrontClient->createInvalidation([
                'DistributionId' => $this->distributionId,
                'InvalidationBatch' => [
                    'CallerReference' => 'mumzworld-reviews-' . time(),
                    'Paths' => [
                        'Quantity' => count($formattedPaths),
                        'Items' => $formattedPaths,
                    ],
                ],
            ]);

            Log::info('CloudFront invalidation created', [
                'invalidation_id' => $result['Invalidation']['Id'],
                'status' => $result['Invalidation']['Status'],
                'paths' => $formattedPaths,
            ]);
        } catch (AwsException $e) {
            Log::error('CloudFront invalidation failed', [
                'error' => $e->getMessage(),
                'paths' => $this->paths,
            ]);

            throw $e;
        }
    }

    /**
     * Create CloudFront client
     *
     * @return \Aws\CloudFront\CloudFrontClient
     */
    protected function createCloudFrontClient(): \Aws\CloudFront\CloudFrontClient
    {
        return new CloudFrontClient([
            'version' => 'latest',
            'region' => config('services.cloudfront.region', 'us-east-1'),
            'credentials' => [
                'key' => config('services.cloudfront.key', config('services.aws.key')),
                'secret' => config('services.cloudfront.secret', config('services.aws.secret')),
            ],
        ]);
    }
}
