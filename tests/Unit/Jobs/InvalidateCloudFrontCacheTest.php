<?php

namespace Tests\Unit\Jobs;

use App\Jobs\InvalidateCloudFrontCache;
use Aws\CloudFront\CloudFrontClient;
use Aws\CommandInterface;
use Aws\Result;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class InvalidateCloudFrontCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Log::spy();
    }

    public function testHandleSuccessfulInvalidation()
    {
        // Mock the CloudFront client
        $mockClient = Mockery::mock(CloudFrontClient::class);
        $mockCommand = Mockery::mock(CommandInterface::class);

        // Set up the expected method calls
        $mockClient->shouldReceive('createInvalidation')
            ->once()
            ->andReturn($mockCommand);

        $mockClient->shouldReceive('execute')
            ->once()
            ->with($mockCommand)
            ->andReturn(new Result([
                'Invalidation' => [
                    'Id' => 'test-invalidation-id',
                    'Status' => 'InProgress',
                ],
            ]));

        // Create the job with test paths
        $paths = ['/reviews/123/*', '/reviews/456/*'];
        $job = new InvalidateCloudFrontCache($paths);

        // Use reflection to replace the createCloudFrontClient method
        $reflectionClass = new \ReflectionClass($job);
        $reflectionMethod = $reflectionClass->getMethod('createCloudFrontClient');
        $reflectionMethod->setAccessible(true);

        // Replace the method with our mock
        $reflectionProperty = $reflectionClass->getProperty('cloudFrontClient');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($job, $mockClient);

        // Execute the job
        $job->handle();

        // Verify logging
        Log::shouldHaveReceived('info')
            ->with(
                'CloudFront invalidation created successfully',
                Mockery::on(function ($data) {
                    return isset($data['invalidation_id']) &&
                        isset($data['status']) &&
                        isset($data['paths']);
                })
            );
    }

    public function testHandleWithException()
    {
        // Mock the CloudFront client to throw an exception
        $mockClient = Mockery::mock(CloudFrontClient::class);
        $exception = new \Exception('Test exception');

        $mockClient->shouldReceive('createInvalidation')
            ->once()
            ->andThrow($exception);

        // Create the job with test paths
        $paths = ['/reviews/123/*'];
        $job = new InvalidateCloudFrontCache($paths);

        // Use reflection to replace the createCloudFrontClient method
        $reflectionClass = new \ReflectionClass($job);
        $reflectionProperty = $reflectionClass->getProperty('cloudFrontClient');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($job, $mockClient);

        // Execute the job
        $job->handle();

        // Verify error logging
        Log::shouldHaveReceived('error')
            ->with(
                'Failed to create CloudFront invalidation',
                Mockery::on(function ($data) {
                    return isset($data['error']) &&
                        isset($data['paths']);
                })
            );
    }

    public function testConstructorWithDefaultDistributionId()
    {
        // Set a config value for the distribution ID
        config(['services.cloudfront.distribution_id' => 'test-distribution-id']);

        // Create the job
        $paths = ['/reviews/123/*'];
        $job = new InvalidateCloudFrontCache($paths);

        // Use reflection to check the distribution ID
        $reflectionClass = new \ReflectionClass($job);
        $reflectionProperty = $reflectionClass->getProperty('distributionId');
        $reflectionProperty->setAccessible(true);

        $this->assertEquals('test-distribution-id', $reflectionProperty->getValue($job));
    }

    public function testConstructorWithCustomDistributionId()
    {
        // Create the job with a custom distribution ID
        $paths = ['/reviews/123/*'];
        $customDistributionId = 'custom-distribution-id';
        $job = new InvalidateCloudFrontCache($paths, $customDistributionId);

        // Use reflection to check the distribution ID
        $reflectionClass = new \ReflectionClass($job);
        $reflectionProperty = $reflectionClass->getProperty('distributionId');
        $reflectionProperty->setAccessible(true);

        $this->assertEquals($customDistributionId, $reflectionProperty->getValue($job));
    }
}
