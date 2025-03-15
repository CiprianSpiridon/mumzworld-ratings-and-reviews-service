<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteRatingAndReviewRequest;
use App\Http\Requests\GetProductReviewsRequest;
use App\Http\Requests\StoreRatingAndReviewRequest;
use App\Http\Requests\UpdatePublicationStatusRequest;
use App\Http\Resources\RatingAndReviewResource;
use App\Models\RatingAndReview;
use App\Services\MediaUploadService;
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
     * Create a new controller instance.
     *
     * @param MediaUploadService $mediaUploadService
     */
    public function __construct(MediaUploadService $mediaUploadService)
    {
        $this->mediaUploadService = $mediaUploadService;
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

        return new RatingAndReviewResource($review);
    }

    /**
     * Display reviews for a specific product.
     */
    public function getProductReviews(GetProductReviewsRequest $request, string $id): AnonymousResourceCollection
    {
        $query = RatingAndReview::where('product_id', $id);

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

        // Get the results as an array and convert to collection
        $perPage = $request->input('per_page', 15);
        $results = $query->get()->toArray();

        // Create a paginator manually
        $page = $request->input('page', 1);
        $total = count($results);
        $items = array_slice($results, ($page - 1) * $perPage, $perPage);

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($items)->map(function ($item) {
                return new RatingAndReview($item);
            }),
            $total,
            $perPage,
            $page,
            ['path' => $request->url()]
        );

        return RatingAndReviewResource::collection($paginator);
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

        $review->delete();

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

        return new RatingAndReviewResource($review);
    }
}
