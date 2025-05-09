<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteRatingAndReviewRequest;
use App\Http\Requests\StoreRatingAndReviewRequest;
use App\Http\Requests\UpdatePublicationStatusRequest;
use App\Http\Requests\GetReviewsByStatusRequest;
use App\Http\Resources\RatingAndReviewResource;
use App\Models\RatingAndReview;
use App\Services\CloudFrontService;
use App\Services\MediaUploadService;
use App\Services\RatingsAndReviewsStatisticsService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

/**
 * Class ReviewController
 * 
 * Handles API requests related to the general lifecycle, status, and listing of reviews.
 */
class ReviewController extends Controller
{
    protected MediaUploadService $mediaUploadService;
    protected CloudFrontService $cloudFrontService;
    protected RatingsAndReviewsStatisticsService $ratingsAndReviewsStatisticsService;

    /**
     * Create a new controller instance.
     *
     * @param MediaUploadService $mediaUploadService Service for handling media uploads.
     * @param CloudFrontService $cloudFrontService Service for CloudFront cache invalidations.
     * @param RatingsAndReviewsStatisticsService $ratingsAndReviewsStatisticsService Service for managing review statistics.
     */
    public function __construct(
        MediaUploadService $mediaUploadService,
        CloudFrontService $cloudFrontService,
        RatingsAndReviewsStatisticsService $ratingsAndReviewsStatisticsService
    ) {
        $this->mediaUploadService = $mediaUploadService;
        $this->cloudFrontService = $cloudFrontService;
        $this->ratingsAndReviewsStatisticsService = $ratingsAndReviewsStatisticsService;
    }

    /**
     * Store a newly created review in storage.
     * 
     * Handles validated review data, processes optional media uploads, and saves the new review.
     * New reviews default to 'pending' publication status.
     * 
     * @param StoreRatingAndReviewRequest $request The validated request object.
     * @return RatingAndReviewResource The resource representation of the created review.
     */
    public function store(StoreRatingAndReviewRequest $request): RatingAndReviewResource
    {
        $validatedData = $request->validated();
        if (isset($validatedData['media_files'])) {
            unset($validatedData['media_files']);
        }
        $review = new RatingAndReview($validatedData);
        $review->save();
        if ($request->hasFile('media_files')) {
            $mediaItems = [];
            foreach ($request->file('media_files') as $file) {
                $mediaItems[] = $this->mediaUploadService->uploadMedia($file, $review->review_id);
            }
            $review->media = $mediaItems;
            $review->save();
        }
        $this->cloudFrontService->invalidateProductReviewsApi($review->product_id);
        return new RatingAndReviewResource($review);
    }

    /**
     * Remove the specified review from storage.
     * 
     * If the deleted review was published, a job is dispatched to update product statistics.
     * 
     * @param DeleteRatingAndReviewRequest $request
     * @param string $id Review ID to delete.
     * @return JsonResponse
     */
    public function destroy(DeleteRatingAndReviewRequest $request, string $id): JsonResponse
    {
        $review = RatingAndReview::find($id);
        if (!$review) {
            return response()->json(['message' => 'Review not found'], Response::HTTP_NOT_FOUND);
        }
        $productIdToUpdate = $review->product_id;
        $wasPublished = $review->publication_status === 'published';
        $hasMedia = !empty($review->media);
        $mediaItems = $hasMedia ? $review->media : [];
        $review->delete();
        if ($wasPublished) {
            $this->ratingsAndReviewsStatisticsService->queueStatsRecalculation($productIdToUpdate);
        }
        if ($hasMedia) {
            $this->cloudFrontService->invalidateMediaItems($mediaItems);
        }
        $this->cloudFrontService->invalidateReviewApi($id);
        $this->cloudFrontService->invalidateProductReviewsApi($productIdToUpdate);
        return response()->json(['message' => 'Review deleted successfully'], Response::HTTP_OK);
    }

    /**
     * Update the publication status of the specified review.
     * 
     * Dispatches a job to update product statistics after changing the status.
     * 
     * @param UpdatePublicationStatusRequest $request
     * @param string $id Review ID.
     * @return RatingAndReviewResource|JsonResponse
     */
    public function updatePublicationStatus(UpdatePublicationStatusRequest $request, string $id): RatingAndReviewResource|JsonResponse
    {
        try {
            $review = RatingAndReview::find($id);
            if (!$review) {
                return response()->json(['message' => 'Review not found'], Response::HTTP_NOT_FOUND);
            }
            $review->publication_status = $request->publication_status;
            $review->save();
            $this->ratingsAndReviewsStatisticsService->queueStatsRecalculation($review->product_id);
            try {
                $this->cloudFrontService->invalidateReviewApi($id);
                $this->cloudFrontService->invalidateProductReviewsApi($review->product_id);
            } catch (\Exception $e) {
                Log::error('Cache invalidation failed during publication status update', [
                    'review_id' => $id,
                    'error' => $e->getMessage()
                ]);
            }
            return new RatingAndReviewResource($review);
        } catch (\Exception $e) {
            Log::error('Failed to update publication status', [
                'review_id' => $id,
                'status' => $request->publication_status,
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ]);
            return response()->json([
                'message' => 'Failed to update publication status',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get reviews filtered by status and other criteria, with pagination.
     *
     * @param GetReviewsByStatusRequest $request Filter and pagination parameters
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getReviewsByStatus(GetReviewsByStatusRequest $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        // Extract primary filter parameters
        $publicationStatus = $request->input('publication_status', null);
        $userId = $request->input('user_id', null);
        $productId = $request->input('product_id', null);
        $query = RatingAndReview::query();

        // Select appropriate GSI based on primary filter
        if ($publicationStatus) {
            $query->where('publication_status', $publicationStatus)
                ->usingIndex('publication_status-index');
        } elseif ($userId) {
            $query->where('user_id', $userId)
                ->usingIndex('user_id-index');
        } elseif ($productId) {
            $query->where('product_id', $productId)
                ->usingIndex('product_id-index');
        } else {
            // Fallback to product index if no primary filter provided
            Log::debug("[Controller] getReviewsByStatus called without primary indexed filter, defaulting to product_id-index scan for pagination base.");
            $query->usingIndex('product_id-index'); 
        }

        // Apply secondary filters
        if ($request->has('country')) {
            $query->where('country', $request->country);
        }
        if ($request->has('language')) {
            $query->where('original_language', $request->language);
        }

        // Setup pagination limits
        $perPage = $request->input('per_page', 100);
        $perPage = min($perPage, 100); // Cap max results at 100
        $query->limit($perPage);

        // Handle DynamoDB key-based pagination token
        if ($request->has('next_token')) {
            try {
                $lastEvaluatedKey = json_decode($request->next_token, true);
                if ($publicationStatus && !isset($lastEvaluatedKey['publication_status'])) {
                    $lastEvaluatedKey['publication_status'] = $publicationStatus;
                }
                $query->afterKey($lastEvaluatedKey);
            } catch (\Exception $e) {
                Log::error('[Controller] Error parsing next_token for getReviewsByStatus: ' . $e->getMessage(), ['token' => $request->next_token]);
            }
        }

        // Execute query
        $results = $query->get();
        $lastItem = $results->last();
        
        // Generate next page token based on the used index
        $nextPageTokenData = null;
        if ($lastItem) {
            // Determine which index was used and create appropriate token
            switch (true) {
                case !empty($publicationStatus):
                    $nextPageTokenData = ['review_id' => $lastItem->review_id, 'publication_status' => $lastItem->publication_status];
                    break;
                case !empty($userId):
                    $nextPageTokenData = ['review_id' => $lastItem->review_id, 'user_id' => $lastItem->user_id];
                    break;
                default:
                    // Both productId filter case and no-filter case use product_id-index,
                    // so we use product_id in the token for both scenarios
                    $nextPageTokenData = ['review_id' => $lastItem->review_id, 'product_id' => $lastItem->product_id];
            }
        }
        $nextToken = $nextPageTokenData ? json_encode($nextPageTokenData) : null;
        
        // Sort by creation date (newest first)
        $sortedResults = $results->sortByDesc('created_at');
        
        // Handle cache invalidation if requested
        if ($request->boolean('invalidate_cache')) {
            $this->cloudFrontService->invalidatePaths(['/api/reviews']);
        }

        // Create resource collection with pagination metadata
        $collection = RatingAndReviewResource::collection($sortedResults->values());
        $currentPage = $request->input('page', 1);
        $path = url('/api/reviews');
        $queryParams = $request->except(['page', 'next_token']);
        
        // Build response metadata and links
        $additionalData = [];
        $links = ['first' => $path . '?' . http_build_query(array_merge($queryParams, ['page' => 1]))];
        
        if ($nextToken && $results->count() >= $perPage) {
            $additionalData['next_token'] = $nextToken;
            $links['next'] = $path . '?' . http_build_query(array_merge($queryParams, ['next_token' => $nextToken]));
        }
        
        $additionalData['links'] = $links;
        $additionalData['meta'] = [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'path' => $path,
            'from' => (($currentPage - 1) * $perPage) + 1,
            'to' => (($currentPage - 1) * $perPage) + $results->count(),
        ];
        
        // Add debugging info in development environments
        // if (app()->environment('local', 'development')) {
        //     $additionalData['debug'] = [
        //         'total_results_on_page' => $results->count(),
        //         'query_details' => [
        //             'primary_filter_status' => $publicationStatus,
        //             'primary_filter_user' => $userId,
        //             'primary_filter_product' => $productId,
        //         ],
        //         'dynamodb_last_key_for_next_token' => $nextPageTokenData
        //     ];
        // }
        
        $collection->additional($additionalData);
        return $collection;
    }

    /**
     * Check if there are any reviews with a 'pending' publication status.
     *
     * Uses a direct DynamoDB query for efficiency.
     * 
     * @return JsonResponse Containing a boolean 'has_pending_reviews'.
     */
    public function hasPendingReviews(): JsonResponse
    {
        $client = app(\BaoPham\DynamoDb\DynamoDbClientService::class)->getClient();
        $tableName = (new RatingAndReview())->getTable();
        $result = $client->query([
            'TableName' => $tableName,
            'IndexName' => 'publication_status-index',
            'KeyConditionExpression' => 'publication_status = :status',
            'ExpressionAttributeValues' => [
                ':status' => ['S' => 'pending']
            ],
            'Select' => 'COUNT',
            'Limit' => 1
        ]);
        $hasPending = $result['Count'] > 0;
        return response()->json([
            'data' => ['has_pending_reviews' => $hasPending],
            'meta' => ['timestamp' => now()->toIso8601String()]
        ]);
    }

    /**
     * Legacy function used by admin panel - DO NOT MODIFY
     * 
     * "BK" refers to backup implementation required by existing admin panel.
     * Returns counts for each review status plus total.
     * 
     * @return JsonResponse
     */
    public function getReviewCountsByStatusBK(): JsonResponse
    {
        $statuses = ['pending', 'published', 'rejected'];
        $counts = [];
        foreach ($statuses as $status) {
            $reviewCollection = RatingAndReview::query()
                ->where('publication_status', $status)
                ->usingIndex('publication_status-index')
                ->get();
            $counts[$status] = $reviewCollection->count();
        }
        $counts['total'] = array_sum($counts);
        return response()->json([
            'data' => $counts,
            'meta' => ['timestamp' => now()->toIso8601String()]
        ]);
    }
} 