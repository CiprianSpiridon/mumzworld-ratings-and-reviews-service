<?php

namespace App\Services;

use App\Models\RatingAndReview;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    /**
     * Supported languages for translation
     * 
     * @var array
     */
    protected $supportedLanguages = ['en', 'ar'];

    /**
     * Google Cloud Translation API key
     * 
     * @var string
     */
    protected $apiKey;

    /**
     * Google Cloud Translation API endpoint
     * 
     * @var string
     */
    protected $apiEndpoint;

    /**
     * Create a new translation service instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->apiKey = config('services.google_translate.api_key');
        $this->apiEndpoint = config('services.google_translate.endpoint');
    }

    /**
     * Translate a review to all supported languages.
     *
     * @param \App\Models\RatingAndReview $review
     * @return \App\Models\RatingAndReview
     */
    public function translateReview(RatingAndReview $review)
    {
        $originalLanguage = $review->original_language;

        // Validate original language
        if (!in_array($originalLanguage, $this->supportedLanguages)) {
            Log::warning("Unsupported original language: {$originalLanguage} for review ID: {$review->review_id}");
            return $review;
        }

        // Get the source content
        $sourceField = "review_{$originalLanguage}";
        $sourceContent = $review->$sourceField;

        if (empty($sourceContent)) {
            Log::warning("Empty source content for review ID: {$review->review_id}");
            return $review;
        }

        // Translate to each supported language (except the original)
        foreach ($this->supportedLanguages as $targetLanguage) {
            if ($targetLanguage !== $originalLanguage) {
                $targetField = "review_{$targetLanguage}";

                // Skip if translation already exists
                if (!empty($review->$targetField)) {
                    continue;
                }

                try {
                    $translatedText = $this->translate($sourceContent, $originalLanguage, $targetLanguage);
                    $review->$targetField = $translatedText;
                } catch (\Exception $e) {
                    Log::error("Translation failed for review ID: {$review->review_id}, Error: {$e->getMessage()}");
                }
            }
        }

        // Save the updated review with translations
        $review->save();

        return $review;
    }

    /**
     * Translate text from one language to another using Google Cloud Translation API.
     *
     * @param string $text
     * @param string $sourceLanguage
     * @param string $targetLanguage
     * @return string
     * @throws \Exception
     */
    protected function translate($text, $sourceLanguage, $targetLanguage)
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Google Translate API key is not configured');
        }

        if (empty($this->apiEndpoint)) {
            throw new \Exception('Google Translate API endpoint is not configured');
        }

        try {
            $response = Http::get($this->apiEndpoint, [
                'key' => $this->apiKey,
                'q' => $text,
                'source' => $sourceLanguage,
                'target' => $targetLanguage,
                'format' => 'text'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['translations'][0]['translatedText'] ?? '';
            } else {
                throw new \Exception("API request failed: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Translation API error: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Batch translate multiple reviews.
     *
     * @param \Illuminate\Database\Eloquent\Collection $reviews
     * @return void
     */
    public function batchTranslateReviews($reviews)
    {
        foreach ($reviews as $review) {
            $this->translateReview($review);
        }
    }
}
