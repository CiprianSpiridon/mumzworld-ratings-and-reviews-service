<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetTranslatedReviewRequest;
use App\Http\Resources\RatingAndReviewResource;
use App\Models\RatingAndReview;
use App\Services\CloudFrontService;
use App\Services\TranslationService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

/**
 * Class ReviewTranslationController
 * 
 * Handles API requests related to translating reviews.
 */
class ReviewTranslationController extends Controller
{
    protected TranslationService $translationService;
    protected CloudFrontService $cloudFrontService;

    /**
     * Create a new controller instance.
     *
     * @param TranslationService $translationService Service for translating review content.
     * @param CloudFrontService $cloudFrontService Service for CloudFront cache invalidations.
     */
    public function __construct(
        TranslationService $translationService,
        CloudFrontService $cloudFrontService
    ) {
        $this->translationService = $translationService;
        $this->cloudFrontService = $cloudFrontService;
    }

    /**
     * Get a review with translation to the requested language.
     * 
     * If the translation to the target language doesn't already exist, it attempts to generate it on-the-fly.
     * 
     * @param GetTranslatedReviewRequest $request The validated request object containing the target language.
     * @param string $id The ID of the review to translate.
     * @return RatingAndReviewResource|JsonResponse The review resource with translation, or an error response.
     */
    public function getTranslatedReview(GetTranslatedReviewRequest $request, string $id): RatingAndReviewResource|JsonResponse
    {
        $targetLanguage = $request->input('language');

        $review = RatingAndReview::find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], Response::HTTP_NOT_FOUND);
        }

        $targetField = "review_{$targetLanguage}";

        if (empty($review->$targetField)) {
            try {
                $review = $this->translationService->translateReview($review);
                $this->cloudFrontService->invalidateReviewApi($id);
            } catch (\Exception $e) {
                Log::error("[Controller] Translation failed for review ID: {$id} to {$targetLanguage}", [
                    'error' => $e->getMessage(), 
                    'exception_class' => get_class($e)
                ]);
                return response()->json([
                    'message' => 'Translation failed',
                    'error' => $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        return new RatingAndReviewResource($review);
    }
} 