<?php

namespace App\Console\Commands;

use App\Models\RatingAndReview;
use App\Services\RatingsAndReviewsStatisticsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Class BackfillReviewStatisticsCommand
 * 
 * Artisan command to backfill product review statistics.
 * It can process all products or specific product IDs.
 */
class BackfillReviewStatisticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     * Allows specifying product IDs or processing all with chunking.
     *
     * @var string
     */
    protected $signature = 'statistics:backfill 
                            {--product_id=* : Specific product ID(s) to backfill (comma-separated or multiple flags)}
                            {--chunk_size=100 : Number of distinct products to fetch for processing at a time if processing all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill product review statistics for existing products.';

    /**
     * The service responsible for calculating statistics.
     *
     * @var RatingsAndReviewsStatisticsService
     */
    protected RatingsAndReviewsStatisticsService $statisticsService;

    /**
     * Create a new command instance.
     *
     * @param RatingsAndReviewsStatisticsService $statisticsService
     */
    public function __construct(RatingsAndReviewsStatisticsService $statisticsService)
    {
        parent::__construct();
        $this->statisticsService = $statisticsService;
    }

    /**
     * Execute the console command.
     * 
     * Determines the set of product IDs to process (either specified or all unique ones)
     * and then iterates through them, calling the statistics calculation service.
     *
     * @return int Exit code (Command::SUCCESS or Command::FAILURE).
     */
    public function handle(): int
    {
        $specificProductIds = $this->option('product_id');
        $chunkSize = (int) $this->option('chunk_size'); // Used by getAllUniqueProductIds

        // Determine which product IDs to process
        if (!empty($specificProductIds)) {
            $this->info("Starting backfill for specific product IDs: " . implode(', ', $specificProductIds));
            $productIdsToProcess = $specificProductIds;
        } else {
            $this->info("Starting backfill for all products. Fetching unique product IDs (GSI query chunk size: {$chunkSize})...");
            $productIdsToProcess = $this->getAllUniqueProductIds($chunkSize);
            if (empty($productIdsToProcess)) {
                $this->info('No product IDs found to process.');
                return Command::SUCCESS;
            }
            $this->info("Found " . count($productIdsToProcess) . " unique product IDs to process.");
        }

        $bar = $this->output->createProgressBar(count($productIdsToProcess));
        $bar->start();

        $processedCount = 0;
        $successCount = 0;
        $errorCount = 0;

        // Process each product ID
        foreach ($productIdsToProcess as $productId) {
            if (empty(trim($productId))) continue; // Skip if any product ID is empty or just whitespace
            
            Log::info("[BackfillCommand] Processing product ID: {$productId}");
            try {
                $success = $this->statisticsService->calculate($productId);
                if ($success) {
                    $successCount++;
                } else {
                    $this->warn("[BackfillCommand] Statistics calculation service indicated an issue for product ID: {$productId}");
                    $errorCount++;
                }
            } catch (\Exception $e) {
                $this->error("[BackfillCommand] Error processing product ID {$productId}: {$e->getMessage()}");
                Log::error("[BackfillCommand] Exception for product ID {$productId}: ", [
                    'exception_class' => get_class($e),
                    'trace_snippet' => substr($e->getTraceAsString(), 0, 500)
                ]);
                $errorCount++;
            }
            $processedCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Backfill process completed.");
        $this->line("Total Product IDs attempted: {$processedCount}");
        $this->line("Successfully processed: <info>{$successCount}</info>");
        $this->line("Failed/Errors: <error>{$errorCount}</error>");

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Get all unique product IDs from reviews
     *
     * Paginates through the product_id GSI to efficiently retrieve distinct product IDs
     *
     * @param int $chunkSize Items per DynamoDB query during pagination
     * @return array<string> Array of unique product IDs
     */
    protected function getAllUniqueProductIds(int $chunkSize): array
    {
        $uniqueProductIds = [];
        $lastEvaluatedKey = null;
        $iterations = 0;
        $maxIterations = 10000; // Safety limit for large datasets
        $this->info('Fetching unique product IDs from reviews table (GSI pagination)...');
        // Progress bar for fetching IDs (can be long if many reviews)
        $progress = $this->output->createProgressBar(); 
        $progress->start();

        do {
            $iterations++;
            if ($iterations > $maxIterations) {
                Log::warning('[BackfillCommand] Max iterations reached while fetching unique product IDs via GSI.');
                break;
            }

            // Query the GSI, selecting only the product_id
            $query = RatingAndReview::query()
                                    ->usingIndex('product_id-index') 
                                    ->select('product_id') 
                                    ->limit($chunkSize);

            if ($lastEvaluatedKey) {
                $query->afterKey($lastEvaluatedKey);
            }

            $reviewsPage = $query->get();

            if ($reviewsPage->isEmpty()) {
                break; // No more pages
            }

            // Collect unique product IDs from the current page
            foreach ($reviewsPage as $review) {
                if (!empty($review->product_id)) {
                    $uniqueProductIds[$review->product_id] = true; // Use array keys for auto-uniqueness
                }
            }

            // Prepare LastEvaluatedKey for the next iteration
            $lastItem = $reviewsPage->last();
            if ($lastItem) {
                $lastEvaluatedKey = [
                    'product_id' => $lastItem->product_id, // GSI Hash Key
                    'review_id'  => $lastItem->review_id  // Main table Primary Hash Key
                ];
            } else {
                $lastEvaluatedKey = null;
            }
            $progress->advance(); // Advance progress for each fetched page

        } while ($lastEvaluatedKey && !$reviewsPage->isEmpty() && $reviewsPage->count() === $chunkSize);
        
        $progress->finish();
        $this->newLine();

        return array_keys($uniqueProductIds); // Return only the unique product IDs
    }
} 