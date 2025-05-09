<?php

namespace App\Jobs;

use App\Services\RatingsAndReviewsStatisticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Class UpdateProductStatisticsJob
 * 
 * A queued job responsible for triggering the recalculation of rating and review statistics
 * for a specific product.
 */
class UpdateProductStatisticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The ID of the product for which statistics need to be updated.
     *
     * @var string
     */
    protected string $productId;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before the job fails.
     *
     * @var int
     */
    public $maxExceptions = 1;

    /**
     * Calculate the number of seconds to wait before retrying the job after an exception.
     * Defines a backoff strategy for retries.
     *
     * @return array<int, int> Seconds to wait for each attempt (e.g., [60, 180, 300] for 1m, 3m, 5m).
     */
    public function backoff(): array
    {
        return [60, 180, 300]; // 1 min, 3 mins, 5 mins
    }

    /**
     * Create a new job instance.
     *
     * @param string $productId The ID of the product requiring statistics update.
     */
    public function __construct(string $productId)
    {
        $this->productId = $productId;
    }

    /**
     * Execute the job.
     * 
     * This method is called when the job is processed by a queue worker.
     * It resolves the RatingsAndReviewsStatisticsService and calls its calculate method.
     *
     * @param RatingsAndReviewsStatisticsService $statisticsService The service responsible for calculations.
     * @return void
     * @throws \Exception Re-throws exceptions to allow Laravel's queue worker to handle retries/failure.
     */
    public function handle(RatingsAndReviewsStatisticsService $statisticsService): void
    {
        Log::info("[Job] UpdateProductStatisticsJob started for product ID: {$this->productId}");
        try {
            $success = $statisticsService->calculate($this->productId);
            if ($success) {
                Log::info("[Job] UpdateProductStatisticsJob completed successfully for product ID: {$this->productId}");
            } else {
                Log::warning("[Job] UpdateProductStatisticsJob: calculate method indicated an issue or no data for product ID: {$this->productId}");
            }
        } catch (\Exception $e) {
            Log::error("[Job] UpdateProductStatisticsJob unhandled exception for product ID: {$this->productId}. Error: " . $e->getMessage(), [
                'exception_class' => get_class($e),
                'trace_snippet' => substr($e->getTraceAsString(), 0, 500) // Avoid overly long log messages
            ]);
            // Rethrow the exception to allow Laravel's queue worker to handle retries/failure
            throw $e;
        }
    }
} 