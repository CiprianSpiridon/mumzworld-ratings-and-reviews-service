<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteRatingAndReviewRequest;
use App\Http\Requests\GetProductReviewsRequest;
use App\Http\Requests\GetTranslatedReviewRequest;
use App\Http\Requests\StoreRatingAndReviewRequest;
use App\Http\Requests\UpdatePublicationStatusRequest;
use App\Http\Resources\RatingAndReviewResource;
use App\Models\RatingAndReview;
use App\Services\CloudFrontService;
use App\Services\MediaUploadService;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RatingAndReviewController extends Controller
{
    /**
     * @var MediaUploadService
     */
    protected $mediaUploadService;

    /**
     * @var TranslationService
     */
    protected $translationService;

    /**
     * @var CloudFrontService
     */
    protected $cloudFrontService;

    /**
     * Create a new controller instance.
     *
     * @param MediaUploadService $mediaUploadService
     * @param TranslationService $translationService
     * @param CloudFrontService $cloudFrontService
     */
    public function __construct(
        MediaUploadService $mediaUploadService,
        TranslationService $translationService,
        CloudFrontService $cloudFrontService
    ) {
        $this->mediaUploadService = $mediaUploadService;
        $this->translationService = $translationService;
        $this->cloudFrontService = $cloudFrontService;
    }

    /**
     * Store a newly created review in storage.
     */
    public function store(StoreRatingAndReviewRequest $request)
    {
        // Create the review with validated data
        $validatedData = $request->validated();

        // Remove media_files from validated data as it's not a model attribute
        if (isset($validatedData['media_files'])) {
            unset($validatedData['media_files']);
        }

        $review = new RatingAndReview($validatedData);
        $review->save();

        // Process uploaded media files if any
        if ($request->hasFile('media_files')) {
            $mediaItems = [];

            foreach ($request->file('media_files') as $file) {
                $mediaItems[] = $this->mediaUploadService->uploadMedia($file, $review->review_id);
            }

            // Update the review with media metadata
            $review->media = $mediaItems;
            $review->save();
        }

        // Invalidate product reviews API cache
        $this->cloudFrontService->invalidateProductReviewsApi($review->product_id);

        return new RatingAndReviewResource($review);
    }

    /**
     * Display reviews for a specific product.
     * 
     * This method retrieves all reviews for a product with optional filtering.
     * 
     * Performance considerations:
     * 1. Uses GSI (Global Secondary Index) for efficient product_id lookups
     * 2. Applies filters directly in the DynamoDB query
     * 3. No pagination is applied - returns all matching reviews
     */
    public function getProductReviews(GetProductReviewsRequest $request, string $id)
    {
        // Use the product_id-index GSI for efficient querying
        $query = RatingAndReview::query()->where('product_id', $id)->usingIndex('product_id-index');

        // Apply filters if provided
        if ($request->has('country')) {
            $query->where('country', $request->country);
        }

        if ($request->has('language')) {
            $query->where('original_language', $request->language);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Default to published reviews only unless specified
        if (!$request->has('publication_status')) {
            $query->where('publication_status', 'published');
        } elseif ($request->publication_status !== 'all') {
            $query->where('publication_status', $request->publication_status);
        }

        // Execute the query and get all results
        $results = $query->get();

        // Build the response
        $response = [
            'data' => $results,
        ];

        return response()->json($response);
    }

    /**
     * Remove the specified review from storage.
     */
    public function destroy(DeleteRatingAndReviewRequest $request, string $id)
    {
        $review = RatingAndReview::find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if review has media before deleting
        $hasMedia = !empty($review->media);

        // Store media items for invalidation
        $mediaItems = $hasMedia ? $review->media : [];

        // Store product ID for API cache invalidation
        $productId = $review->product_id;

        $review->delete();

        // Invalidate CloudFront cache for the review's media if it had any
        if ($hasMedia) {
            $this->cloudFrontService->invalidateMediaItems($mediaItems);
        }

        // Invalidate API cache for this review and product
        $this->cloudFrontService->invalidateReviewApi($id);
        $this->cloudFrontService->invalidateProductReviewsApi($productId);

        return response()->json(['message' => 'Review deleted successfully'], Response::HTTP_OK);
    }

    /**
     * Update the publication status of the specified review.
     */
    public function updatePublicationStatus(UpdatePublicationStatusRequest $request, string $id)
    {
        $review = RatingAndReview::find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], Response::HTTP_NOT_FOUND);
        }

        $review->publication_status = $request->publication_status;
        $review->save();

        // Invalidate API cache for this review and its product
        $this->cloudFrontService->invalidateReviewApi($id);
        $this->cloudFrontService->invalidateProductReviewsApi($review->product_id);

        return new RatingAndReviewResource($review);
    }

    /**
     * Get a review with translation to the requested language.
     *
     * @param GetTranslatedReviewRequest $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse|\App\Http\Resources\RatingAndReviewResource
     */
    public function getTranslatedReview(GetTranslatedReviewRequest $request, string $id)
    {
        $targetLanguage = $request->input('language');

        // Find the review
        $review = RatingAndReview::find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the translation already exists
        $targetField = "review_{$targetLanguage}";

        // If translation doesn't exist or is empty, translate it
        if (empty($review->$targetField)) {
            try {
                $review = $this->translationService->translateReview($review);

                // Invalidate API cache for this review since we've added a translation
                $this->cloudFrontService->invalidateReviewApi($id);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Translation failed',
                    'error' => $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return new RatingAndReviewResource($review);
    }
}
