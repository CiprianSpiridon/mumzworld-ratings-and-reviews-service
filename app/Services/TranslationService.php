<?php

namespace App\Services;

use App\Models\RatingAndReview;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Class TranslationService
 * 
 * Handles translation of review text between supported languages (English and Arabic)
 * using the Google Cloud Translation API.
 */
class TranslationService
{
    /**
     * Supported languages for translation (e.g., 'en', 'ar').
     * 
     * @var array<int, string>
     */
    protected array $supportedLanguages = ['en', 'ar'];

    /**
     * Google Cloud Translation API key, fetched from config('services.google_translate.api_key').
     * 
     * @var string|null
     */
    protected ?string $apiKey;

    /**
     * Google Cloud Translation API endpoint, fetched from config('services.google_translate.endpoint').
     * 
     * @var string|null
     */
    protected ?string $apiEndpoint;

    /**
     * Create a new translation service instance.
     * Initializes API key and endpoint from configuration.
     */
    public function __construct()
    {
        $this->apiKey = config('services.google_translate.api_key');
        $this->apiEndpoint = config('services.google_translate.endpoint');
    }

    /**
     * Translate a single review to all other supported languages if translations are missing.
     *
     * The review's original language is used as the source. Translations are saved back to the review model.
     *
     * @param RatingAndReview $review The review model instance to translate.
     * @return RatingAndReview The updated review model instance with translations (or original if errors/no translation needed).
     */
    public function translateReview(RatingAndReview $review): RatingAndReview
    {
        $originalLanguage = $review->original_language;

        // Validate that the review's original language is supported by this service
        if (!in_array($originalLanguage, $this->supportedLanguages)) {
            Log::warning("[TranslationService] Unsupported original language: {$originalLanguage} for review ID: {$review->review_id}. Skipping translation.");
            return $review;
        }

        // Determine the source text field (e.g., review_en or review_ar)
        $sourceField = "review_{$originalLanguage}";
        $sourceContent = $review->$sourceField;

        if (empty($sourceContent)) {
            Log::warning("[TranslationService] Empty source content for review ID: {$review->review_id} (lang: {$originalLanguage}). Skipping translation.");
            return $review;
        }

        $translationOccurred = false;
        // Iterate through all supported languages to find target languages
        foreach ($this->supportedLanguages as $targetLanguage) {
            // Do not translate to the original language itself
            if ($targetLanguage !== $originalLanguage) {
                $targetField = "review_{$targetLanguage}";

                // Skip if this specific translation already exists and is not empty
                if (!empty($review->$targetField)) {
                    continue;
                }

                // Perform the translation
                try {
                    Log::debug("[TranslationService] Translating review ID: {$review->review_id} from {$originalLanguage} to {$targetLanguage}");
                    $translatedText = $this->translate($sourceContent, $originalLanguage, $targetLanguage);
                    if (!empty($translatedText)) {
                        $review->$targetField = $translatedText;
                        $translationOccurred = true;
                    } else {
                        Log::warning("[TranslationService] Translation returned empty for review ID: {$review->review_id} from {$originalLanguage} to {$targetLanguage}");
                    }
                } catch (\Exception $e) {
                    // Log error but continue to attempt other language translations for this review
                    Log::error("[TranslationService] Translation failed for review ID: {$review->review_id} (from {$originalLanguage} to {$targetLanguage}). Error: {$e->getMessage()}");
                }
            }
        }

        // Save the review only if any new translations were actually added
        if ($translationOccurred) {
            $review->save();
            Log::info("[TranslationService] Successfully translated and saved review ID: {$review->review_id}");
        }

        return $review;
    }

    /**
     * Translate a given text string from a source language to a target language.
     * Uses the configured Google Cloud Translation API.
     *
     * @param string $text The text to translate.
     * @param string $sourceLanguage The source language code (e.g., 'en').
     * @param string $targetLanguage The target language code (e.g., 'ar').
     * @return string The translated text, or an empty string if translation fails or returns empty.
     * @throws \Exception if API key or endpoint is not configured, or if the API request fails critically.
     */
    protected function translate(string $text, string $sourceLanguage, string $targetLanguage): string
    {
        if (empty($this->apiKey)) {
            Log::error("[TranslationService] Google Translate API key is not configured.");
            throw new \Exception('Google Translate API key is not configured');
        }

        if (empty($this->apiEndpoint)) {
            Log::error("[TranslationService] Google Translate API endpoint is not configured.");
            throw new \Exception('Google Translate API endpoint is not configured');
        }

        try {
            // Make the HTTP GET request to Google Translate API
            $response = Http::get($this->apiEndpoint, [
                'key' => $this->apiKey,
                'q' => $text,
                'source' => $sourceLanguage,
                'target' => $targetLanguage,
                'format' => 'text' // Request plain text translation
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Extract the translated text from the API response structure
                $translatedText = $data['data']['translations'][0]['translatedText'] ?? '';
                if (empty($translatedText)) {
                    Log::warning("[TranslationService] Google API returned empty translatedText for source: '{$sourceLanguage}', target: '{$targetLanguage}'. Input: " . Str::limit($text, 50));
                }
                return $translatedText;
            } else {
                Log::error("[TranslationService] Google Translate API request failed with status {$response->status()}. Body: " . $response->body());
                throw new \Exception("Google Translate API request failed: " . $response->body());
            }
        } catch (\Exception $e) {
            // Log detailed error and rethrow to allow calling method to handle (or for job retries etc.)
            Log::error("[TranslationService] Exception during Google Translate API call: {$e->getMessage()}", [
                'source_lang' => $sourceLanguage,
                'target_lang' => $targetLanguage,
                'exception_class' => get_class($e),
                'trace_snippet' => substr($e->getTraceAsString(), 0, 200)
            ]);
            throw $e; // Re-throw the exception
        }
    }

    /**
     * Batch translate a collection of reviews.
     *
     * Iterates through each review and calls translateReview().
     *
     * @param Collection<int, RatingAndReview> $reviews A collection of RatingAndReview models.
     * @return void
     */
    public function batchTranslateReviews(Collection $reviews): void
    {
        if ($reviews->isEmpty()) {
            Log::info("[TranslationService] batchTranslateReviews called with an empty collection. Nothing to do.");
            return;
        }
        Log::info("[TranslationService] Starting batch translation for " . $reviews->count() . " reviews.");
        foreach ($reviews as $review) {
            // Individual errors within translateReview are logged there
            $this->translateReview($review);
        }
        Log::info("[TranslationService] Finished batch translation.");
    }
}
