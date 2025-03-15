<?php

namespace Tests\Feature;

use App\Models\RatingAndReview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RatingAndReviewTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test creating a review with media files.
     */
    public function test_can_create_review_with_media(): void
    {
        $image = UploadedFile::fake()->image('review-image.jpg');
        $video = UploadedFile::fake()->create('review-video.mp4', 1000, 'video/mp4');

        $reviewData = [
            'user_id' => $this->faker->uuid,
            'product_id' => $this->faker->uuid,
            'rating' => $this->faker->numberBetween(1, 5),
            'original_language' => 'en',
            'review_en' => $this->faker->paragraph,
            'country' => 'AE',
            'media_files' => [$image, $video]
        ];

        $response = $this->postJson('/api/reviews', $reviewData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'review_id',
                    'user_id',
                    'product_id',
                    'rating',
                    'original_language',
                    'review_en',
                    'country',
                    'media'
                ]
            ]);

        // Assert media was stored
        $reviewId = $response->json('data.review_id');
        $mediaItems = $response->json('data.media');

        $this->assertCount(2, $mediaItems);

        // Check that files were stored
        foreach ($mediaItems as $media) {
            $this->assertTrue(Storage::disk('public')->exists($media['path']));
            $this->assertContains($media['type'], ['image', 'video']);
        }

        // Assert review was saved to database
        $this->assertDatabaseHas('ratings_and_reviews', [
            'review_id' => $reviewId,
            'user_id' => $reviewData['user_id'],
            'product_id' => $reviewData['product_id'],
        ]);
    }

    /**
     * Test creating a review without media files.
     */
    public function test_can_create_review_without_media(): void
    {
        $reviewData = [
            'user_id' => $this->faker->uuid,
            'product_id' => $this->faker->uuid,
            'rating' => $this->faker->numberBetween(1, 5),
            'original_language' => 'en',
            'review_en' => $this->faker->paragraph,
            'country' => 'AE',
        ];

        $response = $this->postJson('/api/reviews', $reviewData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'review_id',
                    'user_id',
                    'product_id',
                    'rating',
                    'original_language',
                    'review_en',
                    'country',
                ]
            ]);

        // Assert review was saved to database
        $this->assertDatabaseHas('ratings_and_reviews', [
            'review_id' => $response->json('data.review_id'),
            'user_id' => $reviewData['user_id'],
            'product_id' => $reviewData['product_id'],
        ]);
    }
}
