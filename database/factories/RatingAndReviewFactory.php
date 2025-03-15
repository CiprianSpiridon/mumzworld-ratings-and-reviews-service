<?php

namespace Database\Factories;

use App\Models\RatingAndReview;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Str;

class RatingAndReviewFactory
{
    protected $faker;

    public function __construct()
    {
        $this->faker = FakerFactory::create();
    }

    /**
     * Create a new rating and review with random data.
     *
     * @param array $attributes
     * @return \App\Models\RatingAndReview
     */
    public function make(array $attributes = [])
    {
        $languages = ['en', 'ar'];
        $originalLanguage = $attributes['original_language'] ?? $this->faker->randomElement($languages);
        $countries = ['AE', 'SA', 'KW', 'QA', 'BH', 'OM'];

        $defaultAttributes = [
            'review_id' => (string) Str::uuid(),
            'user_id' => (string) Str::uuid(),
            'product_id' => (string) Str::uuid(),
            'rating' => $this->faker->numberBetween(1, 5),
            'original_language' => $originalLanguage,
            'review_en' => $originalLanguage === 'en'
                ? $this->faker->paragraph(3)
                : $this->faker->sentence(10) . ' (Translated from Arabic)',
            'review_ar' => $originalLanguage === 'ar'
                ? $this->faker->paragraph(3)
                : $this->faker->sentence(10) . ' (مترجم من الإنجليزية)',
            'country' => $this->faker->randomElement($countries),
            'created_at' => $this->faker->dateTimeThisYear()->format('Y-m-d\TH:i:s\Z'),
            'media' => [],
            'publication_status' => $this->faker->randomElement(['pending', 'published', 'rejected']),
        ];

        $mergedAttributes = array_merge($defaultAttributes, $attributes);

        return new RatingAndReview($mergedAttributes);
    }

    /**
     * Create and persist a new rating and review with random data.
     *
     * @param array $attributes
     * @return \App\Models\RatingAndReview
     */
    public function create(array $attributes = [])
    {
        $review = $this->make($attributes);
        $review->save();

        return $review;
    }

    /**
     * Create multiple rating and review instances.
     *
     * @param int $count
     * @param array $attributes
     * @return array
     */
    public function makeMultiple(int $count, array $attributes = [])
    {
        $reviews = [];

        for ($i = 0; $i < $count; $i++) {
            $reviews[] = $this->make($attributes);
        }

        return $reviews;
    }

    /**
     * Create a published review.
     *
     * @param array $attributes
     * @return \App\Models\RatingAndReview
     */
    public function makePublished(array $attributes = [])
    {
        return $this->make(array_merge($attributes, [
            'publication_status' => 'published',
        ]));
    }

    /**
     * Generate a placeholder image URL.
     *
     * @param int $width
     * @param int $height
     * @return string
     */
    private function getPlaceholderImageUrl(int $width = 800, int $height = 600): string
    {
        $services = [
            "https://picsum.photos/{width}/{height}", // Lorem Picsum
            "https://placehold.co/{width}x{height}", // Placehold.co
            "https://loremflickr.com/{width}/{height}", // LoremFlickr
            "https://source.unsplash.com/random/{width}x{height}", // Unsplash
        ];

        $url = $this->faker->randomElement($services);
        return str_replace(['{width}', '{height}'], [$width, $height], $url);
    }

    /**
     * Generate a placeholder video URL.
     *
     * @return string
     */
    private function getPlaceholderVideoUrl(): string
    {
        // Sample video IDs from YouTube
        $videoIds = [
            'dQw4w9WgXcQ', // Rick Astley - Never Gonna Give You Up
            'jNQXAC9IVRw', // Me at the zoo
            'hY7m5jjJ9mM', // CATS will make you LAUGH YOUR HEAD OFF
            'QH2-TGUlwu4', // Nyan Cat
            'ZyhrYis509A', // Aqua - Barbie Girl
        ];

        return 'https://www.youtube.com/watch?v=' . $this->faker->randomElement($videoIds);
    }

    /**
     * Create a review with multiple media items.
     *
     * @param int $count
     * @param array $attributes
     * @return \App\Models\RatingAndReview
     */
    public function makeWithMultipleMedia(int $count = 3, array $attributes = [])
    {
        $review = $this->make($attributes);
        $media = [];

        // Generate fake media metadata
        for ($i = 0; $i < $count; $i++) {
            // Randomly choose between image and video
            $isVideo = $this->faker->boolean(30); // 30% chance of being a video

            $mediaId = 'media-' . Str::random(8);

            if ($isVideo) {
                $media[] = [
                    'id' => $mediaId,
                    'type' => 'video',
                    'path' => "reviews/{$review->review_id}/{$mediaId}.mp4",
                    'url' => $this->getPlaceholderVideoUrl(),
                ];
            } else {
                $width = $this->faker->numberBetween(800, 1200);
                $height = $this->faker->numberBetween(600, 900);

                $media[] = [
                    'id' => $mediaId,
                    'type' => 'image',
                    'path' => "reviews/{$review->review_id}/{$mediaId}.jpg",
                    'url' => $this->getPlaceholderImageUrl($width, $height),
                ];
            }
        }

        $review->media = $media;
        $review->save();

        return $review;
    }

    /**
     * Create a review with a single image.
     *
     * @param array $attributes
     * @return \App\Models\RatingAndReview
     */
    public function makeWithImage(array $attributes = [])
    {
        $review = $this->make($attributes);
        $mediaId = 'media-' . Str::random(8);
        $width = $this->faker->numberBetween(800, 1200);
        $height = $this->faker->numberBetween(600, 900);

        $review->media = [
            [
                'id' => $mediaId,
                'type' => 'image',
                'path' => "reviews/{$review->review_id}/{$mediaId}.jpg",
                'url' => $this->getPlaceholderImageUrl($width, $height),
            ]
        ];

        $review->save();
        return $review;
    }

    /**
     * Create a review with a single video.
     *
     * @param array $attributes
     * @return \App\Models\RatingAndReview
     */
    public function makeWithVideo(array $attributes = [])
    {
        $review = $this->make($attributes);
        $mediaId = 'media-' . Str::random(8);

        $review->media = [
            [
                'id' => $mediaId,
                'type' => 'video',
                'path' => "reviews/{$review->review_id}/{$mediaId}.mp4",
                'url' => $this->getPlaceholderVideoUrl(),
            ]
        ];

        $review->save();
        return $review;
    }
}
