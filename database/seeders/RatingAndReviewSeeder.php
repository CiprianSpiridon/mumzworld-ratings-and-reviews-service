<?php

namespace Database\Seeders;

use App\Models\RatingAndReview;
use Database\Factories\RatingAndReviewFactory;
use Illuminate\Database\Seeder;

class RatingAndReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $factory = new RatingAndReviewFactory();
        $totalReviews = 100000;
        $batchSize = 25; // Smaller batch size to avoid throttling
        $progressStep = 1000; // Show progress every 1000 reviews

        $this->command->info("Starting to create {$totalReviews} ratings and reviews...");
        $this->command->getOutput()->progressStart($totalReviews);

        // Create reviews in batches to manage memory and avoid throttling
        for ($i = 0; $i < $totalReviews; $i += $batchSize) {
            $currentBatchSize = min($batchSize, $totalReviews - $i);
            $reviews = [];

            // Generate the batch
            for ($j = 0; $j < $currentBatchSize; $j++) {
                $reviews[] = $factory->make();
            }

            // Save the batch
            foreach ($reviews as $review) {
                $review->save();
                $this->command->getOutput()->progressAdvance();
            }

            // Free up memory
            unset($reviews);

            // Small delay to avoid throttling
            usleep(100000); // 100ms

            // Show progress
            if (($i + $currentBatchSize) % $progressStep === 0 || ($i + $currentBatchSize) === $totalReviews) {
                $this->command->info(" Processed " . ($i + $currentBatchSize) . " of {$totalReviews} reviews");
            }
        }

        $this->command->getOutput()->progressFinish();
        $this->command->info("Created {$totalReviews} ratings and reviews successfully!");

        // Create 10 published reviews for a specific product (for testing)
        $this->command->info("Creating 10 published reviews with images for a specific product...");
        $productId = '12345678-1234-1234-1234-123456789012';

        for ($i = 0; $i < 10; $i++) {
            $factory->makeWithImage([
                'product_id' => $productId,
                'publication_status' => 'published',
            ]);
        }

        // Create 5 reviews with videos for the same product
        $this->command->info("Creating 5 published reviews with videos for the same product...");
        for ($i = 0; $i < 5; $i++) {
            $factory->makeWithVideo([
                'product_id' => $productId,
                'publication_status' => 'published',
            ]);
        }

        // Create 5 reviews with multiple media items (for testing)
        $this->command->info("Creating 5 reviews with multiple media items...");
        for ($i = 0; $i < 5; $i++) {
            $factory->makeWithMultipleMedia(rand(2, 4));
        }

        // Create reviews with different publication statuses
        $this->command->info("Creating reviews with different publication statuses...");
        $statuses = ['pending', 'published', 'rejected'];

        foreach ($statuses as $status) {
            $this->command->info("Creating 3 reviews with {$status} status and media...");
            for ($i = 0; $i < 3; $i++) {
                $factory->makeWithMultipleMedia(2, [
                    'publication_status' => $status,
                ]);
            }
        }

        $this->command->info("Seeding completed successfully!");
    }
}
