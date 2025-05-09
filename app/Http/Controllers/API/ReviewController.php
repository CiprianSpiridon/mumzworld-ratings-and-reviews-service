<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteRatingAndReviewRequest;
use App\Http\Requests\StoreRatingAndReviewRequest;
use App\Http\Requests\UpdatePublicationStatusRequest;
use App\Http\Requests\GetReviewsByStatusRequest;
use App\Http\Resources\RatingAndReviewResource;
use App\Models\RatingAndReview;
// RatingsAndReviewStatistics model is not directly used by methods in this controller, only its service for queueing.
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
     * Get reviews, optionally filtered by status and other criteria, with pagination.
     * 
     * Supports filtering by publication_status, user_id, product_id, country, and language.
     * Implements DynamoDB key-based pagination using a 'next_token'.
     * Allows cache invalidation via a query parameter.
     *
     * @param GetReviewsByStatusRequest $request The validated request with filter and pagination parameters.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection The collection of reviews.
     */
    public function getReviewsByStatus(GetReviewsByStatusRequest $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $publicationStatus = $request->input('publication_status', null);
        $userId = $request->input('user_id', null);
        $productId = $request->input('product_id', null);
        $query = RatingAndReview::query();
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
            Log::debug("[Controller] getReviewsByStatus called without primary indexed filter, defaulting to product_id-index scan for pagination base.");
            $query->usingIndex('product_id-index'); 
        }
        if ($request->has('country')) {
            $query->where('country', $request->country);
        }
        if ($request->has('language')) {
            $query->where('original_language', $request->language);
        }
        $perPage = $request->input('per_page', 100);
        $perPage = min($perPage, 100);
        $query->limit($perPage);
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
        $results = $query->get();
        $lastItem = $results->last();
        $nextPageTokenData = null;
        if ($lastItem) {
            if ($publicationStatus) {
                $nextPageTokenData = ['review_id' => $lastItem->review_id, 'publication_status' => $lastItem->publication_status];
            } elseif ($userId) {
                $nextPageTokenData = ['review_id' => $lastItem->review_id, 'user_id' => $lastItem->user_id];
            } elseif ($productId) {
                $nextPageTokenData = ['review_id' => $lastItem->review_id, 'product_id' => $lastItem->product_id];
            } else {
                $nextPageTokenData = ['review_id' => $lastItem->review_id, 'product_id' => $lastItem->product_id];
            }
        }
        $nextToken = $nextPageTokenData ? json_encode($nextPageTokenData) : null;
        $sortedResults = $results->sortByDesc('created_at');
        if ($request->boolean('invalidate_cache')) {
            $this->cloudFrontService->invalidatePaths(['/api/reviews']);
        }
        $collection = RatingAndReviewResource::collection($sortedResults->values());
        $currentPage = $request->input('page', 1);
        $path = url('/api/reviews');
        $queryParams = $request->except(['page', 'next_token']);
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
        if (app()->environment('local', 'development')) {
            $additionalData['debug'] = [
                'total_results_on_page' => $results->count(),
                'query_details' => [
                    'primary_filter_status' => $publicationStatus,
                    'primary_filter_user' => $userId,
                    'primary_filter_product' => $productId,
                ],
                'dynamodb_last_key_for_next_token' => $nextPageTokenData
            ];
        }
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
     * DO NOT TOUCH THIS FUNCTION - used by admin panel (BK likely means Backup or for Baskar K.)
     * 
     * Get review counts for each publication status (pending, published, rejected) and a total.
     * Note: This method iterates and counts for each status, which can be less efficient than a single aggregated query if possible.
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