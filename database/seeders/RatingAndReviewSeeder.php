<?php

namespace Database\Seeders;

use App\Models\RatingAndReview;
use Database\Factories\RatingAndReviewFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RatingAndReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $factory = new RatingAndReviewFactory();
        $totalProducts = 50000; // Number of products to create reviews for
        $progressStep = 100; // Show progress every 100 products
        $mediaPercentage = 80; // Percentage of reviews that should have media

        $this->command->info("Starting to create reviews for {$totalProducts} products with {$mediaPercentage}% having media...");
        $this->command->getOutput()->progressStart($totalProducts);

        // Create reviews for each product
        for ($i = 0; $i < $totalProducts; $i++) {
            $productId = (string) Str::uuid(); // Generate a unique product ID
            $reviewCount = rand(1, 20); // Random number of reviews between 1 and 20

            // Create reviews for this product
            for ($j = 0; $j < $reviewCount; $j++) {
                // Determine if this review should have media (80% chance)
                $shouldHaveMedia = rand(1, 100) <= $mediaPercentage;

                if ($shouldHaveMedia) {
                    // Determine the type of media to add
                    $mediaType = rand(1, 100);

                    if ($mediaType <= 60) {
                        // 60% chance of single image
                        $factory->makeWithImage([
                            'product_id' => $productId,
                        ]);
                    } else if ($mediaType <= 80) {
                        // 20% chance of single video
                        $factory->makeWithVideo([
                            'product_id' => $productId,
                        ]);
                    } else {
                        // 20% chance of multiple media (2-4 items)
                        $factory->makeWithMultipleMedia(rand(2, 4), [
                            'product_id' => $productId,
                        ]);
                    }
                } else {
                    // 20% of reviews without media
                    $factory->create([
                        'product_id' => $productId,
                    ]);
                }
            }

            $this->command->getOutput()->progressAdvance();

            // Show progress
            if (($i + 1) % $progressStep === 0 || ($i + 1) === $totalProducts) {
                $this->command->info(" Processed " . ($i + 1) . " of {$totalProducts} products");
            }

            // Small delay to avoid throttling
            usleep(50000); // 50ms
        }

        $this->command->getOutput()->progressFinish();
        $this->command->info("Created reviews for {$totalProducts} products successfully!");

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
