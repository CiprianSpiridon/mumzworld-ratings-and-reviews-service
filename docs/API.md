# Mumzworld Ratings and Reviews API Documentation

This document provides information about the Ratings and Reviews API endpoints, request/response formats, and usage examples.

## API Endpoints

### Create a Review

**Endpoint:** `POST /api/reviews`

Creates a new rating and review for a product.

**Request Body:**

```json
{
  "user_id": "string",
  "product_id": "string",
  "rating": "integer (1-5)",
  "original_language": "string (en|ar)",
  "review_en": "string (required if original_language is en)",
  "review_ar": "string (required if original_language is ar)",
  "country": "string (2-letter country code)",
  "media_files": [file1, file2, ...] (optional)
}
```

**Media Upload:**

The API supports uploading media files (images and videos) with reviews. To upload media:

- Use `multipart/form-data` as the content type
- Add files to the `media_files[]` array field
- Supported file types: jpeg, png, jpg, gif, mp4, mov, avi
- Maximum file size: 10MB per file

**Response:**

```json
{
  "data": {
    "review_id": "string",
    "user_id": "string",
    "product_id": "string",
    "rating": "integer",
    "original_language": "string",
    "review_en": "string",
    "review_ar": "string",
    "country": "string",
    "publication_status": "string",
    "created_at": "datetime",
    "media": [
      {
        "id": "string",
        "type": "string (image|video)",
        "path": "string",
        "url": "string"
      }
    ]
  }
}
```

### Get Reviews by Status

**Endpoint:** `GET /api/reviews`

Retrieves reviews filtered by publication status and other criteria, with pagination.

**Query Parameters:**

- `publication_status` (optional): Filter by publication status (`pending`, `published`, or `rejected`)
- `country` (optional): Filter by 2-letter country code
- `language` (optional): Filter by original language (`en` or `ar`)
- `per_page` (optional): Number of reviews per page (default: 15, max: 100)
- `page` (optional): Page number for pagination (default: 1)
- `next_token` (optional): Pagination token for DynamoDB key-based pagination
- `invalidate_cache` (optional): Set to `true` to invalidate the CloudFront cache for this endpoint

**Response:**

```json
{
  "data": [
    {
      "review_id": "string",
      "user_id": "string",
      "product_id": "string",
      "rating": "integer",
      "original_language": "string",
      "review_en": "string",
      "review_ar": "string",
      "country": "string",
      "publication_status": "string",
      "created_at": "datetime",
      "updated_at": "datetime",
      "media": [
        {
          "media_id": "string",
          "media_type": "string",
          "media_url": "string"
        }
      ]
    }
  ],
  "links": {
    "first": "string (URL)",
    "prev": "string (URL) or null",
    "next": "string (URL) or null"
  },
  "meta": {
    "current_page": "integer",
    "per_page": "integer",
    "path": "string (URL)"
  },
  "next_token": "string or null"
}
```

**Pagination Notes:**

This endpoint uses DynamoDB's key-based pagination for efficient querying of large datasets. The `next_token` parameter is used to retrieve the next page of results:

1. For the first request, omit the `next_token` parameter
2. If the response includes a `next_token` value, use it in your next request to get the next page
3. When `next_token` is null in the response, you've reached the end of the results

**Example Requests:**

```
# First page
GET /api/reviews?publication_status=pending&per_page=10

# Next page (using next_token from previous response)
GET /api/reviews?publication_status=pending&per_page=10&next_token=eyJyZXZpZXdfaWQiOnsicyI6ImFiYzEyMyJ9fQ==
```

### Get Product Reviews

**Endpoint:** `GET /api/products/{id}/reviews`

Retrieves reviews for a specific product.

**Path Parameters:**

- `id` (required): The product ID

**Query Parameters:**

- `country` (optional): Filter reviews by country code
- `language` (optional): Filter reviews by language (en|ar)
- `user_id` (optional): Filter reviews by user ID
- `publication_status` (optional): Filter reviews by publication status

**Performance Notes:**

This endpoint retrieves all reviews for a product with optional filtering. The implementation:

1. Uses a Global Secondary Index (GSI) for efficient product_id lookups.
2. Applies filters directly in the DynamoDB query.
3. Returns all matching reviews without pagination for the reviews themselves.
4. The included `rating_summary` is fetched from a pre-calculated data store, ensuring fast retrieval of statistics. This summary is updated automatically when review statuses change or when published reviews are deleted.

**Response:**

```json
{
  "data": [
    {
      "review_id": "string",
      "user_id": "string",
      "product_id": "string",
      "rating": "integer",
      "original_language": "string",
      "review_en": "string",
      "review_ar": "string",
      "country": "string",
      "publication_status": "string",
      "created_at": "datetime",
      "media": [
        {
          "id": "string",
          "type": "string (image|video)",
          "path": "string",
          "url": "string"
        }
      ]
    }
  ],
  "rating_summary": {
    "average": 4.5,
    "count": 10,
    "distribution": {
      "1": 0,
      "2": 1,
      "3": 1,
      "4": 2,
      "5": 6
    },
    "percentage_distribution": {
      "1": 0,
      "2": 10,
      "3": 10,
      "4": 20,
      "5": 60
    }
  }
}
```

### Get Product Rating Summary

**Endpoint:** `GET /api/products/{id}/rating`

Retrieves the rating summary for a specific product.

**Path Parameters:**

- `id` (required): The product ID

**Performance Notes:**

This endpoint retrieves a pre-calculated rating summary for a product. The implementation:

1. Fetches data directly from a dedicated statistics store (e.g., `ratings_and_review_statistics` DynamoDB table), ensuring very fast responses.
2. The statistics are based on published reviews only.
3. This summary is updated automatically when review statuses change or when published reviews are deleted.
4. If no pre-calculated statistics are found for a product (e.g., it has no published reviews), a default summary with zero counts and average will be returned.

**Response:**

```json
{
  "average": 4.5,
  "count": 10,
  "distribution": {
    "1": 0,
    "2": 1,
    "3": 1,
    "4": 2,
    "5": 6
  },
  "percentage_distribution": {
    "1": 0,
    "2": 10,
    "3": 10,
    "4": 20,
    "5": 60
  }
}
```

### Delete a Review

**Endpoint:** `DELETE /api/reviews/{id}`

Deletes a specific review.

**Path Parameters:**

- `id` (required): The review ID

**Response:**

```json
{
  "message": "Review deleted successfully"
}
```

### Update Publication Status

**Endpoint:** `PUT /api/reviews/{id}/publication`

Updates the publication status of a review.

**URL Parameters:**
- `id` (required): The ID of the review to update

**Request Body:**
```json
{
  "publication_status": "string (pending|published|rejected)"
}
```

**Response:**
```json
{
  "data": {
    "id": "string",
    "user_id": "string",
    "product_id": "string",
    "rating": 5,
    "original_language": "string",
    "review_en": "string",
    "review_ar": "string",
    "country": "string",
    "created_at": "datetime",
    "media": [],
    "publication_status": "string"
  }
}
```

### Get Translated Review

**Endpoint:** `GET /api/reviews/{id}/translate`

Retrieves a review with translation to the requested language. If the translation doesn't exist, it will be created on-demand.

**URL Parameters:**
- `id` (required): The ID of the review to translate

**Query Parameters:**
- `language` (required): The target language code (either 'en' or 'ar')

**Response:**
```json
{
  "data": {
    "id": "string",
    "user_id": "string",
    "product_id": "string",
    "rating": 5,
    "original_language": "string",
    "review_en": "string",
    "review_ar": "string",
    "country": "string",
    "created_at": "datetime",
    "media": [],
    "publication_status": "string"
  }
}
```

**Error Responses:**
- 404 Not Found: If the review doesn't exist
- 422 Unprocessable Entity: If the language parameter is invalid
- 500 Internal Server Error: If translation fails

## Product Rating Statistics

To ensure optimal performance when retrieving product rating summaries (average rating, count, distribution), the API utilizes a pre-calculation mechanism.

- **Pre-calculated Summaries:** Statistics for each product are calculated and stored in a dedicated data store (e.g., a `ratings_and_review_statistics` DynamoDB table).
- **Automatic Updates:** These summaries are automatically updated in the background whenever:
    - A review's publication status changes (e.g., from pending to published, or published to rejected).
    - A published review is deleted.
- **Data Basis:** All calculated statistics are based *only* on reviews that have a `published` status.
- **Fast Retrieval:** Endpoints that return rating summaries (like `GET /api/products/{id}/rating` and the summary part of `GET /api/products/{id}/reviews`) read directly from this pre-calculated store, making these lookups very fast.
- **No Statistics State:** If a product has no published reviews, or if its statistics haven't been calculated yet (e.g., for a brand new product), API endpoints will return a default summary indicating zero reviews and ratings.

## Media Storage

Media files uploaded with reviews are stored using the default filesystem disk configured in `config/filesystems.php`. The default disk can be changed by setting the `FILESYSTEM_DISK` environment variable.

### Storage Options

The application supports several storage options:

1. **Local Storage** (default: 'local')
   - Files are stored in `storage/app/private/reviews/{reviewId}/{mediaId}.{extension}`
   - Access is controlled by the application

2. **Public Storage** (set `FILESYSTEM_DISK=public`)
   - Files are stored in `storage/app/public/reviews/{reviewId}/{mediaId}.{extension}`
   - Public URL: `/storage/reviews/{reviewId}/{mediaId}.{extension}`
   - Requires running `php artisan storage:link` to create the symbolic link

3. **S3 Storage** (set `FILESYSTEM_DISK=s3`)
   - Files are stored in Amazon S3.
   - **URL Generation**:
     - Media URLs will be **CloudFront URLs** if the `AWS_URL` environment variable in your `.env` file is set to your CloudFront distribution's domain (e.g., `AWS_URL=https://yourcloudfrontdomain.cloudfront.net`).
     - If `AWS_URL` is not set, direct **S3 bucket URLs** will be generated.
     - If `AWS_URL` is not set AND essential S3 configuration like `AWS_BUCKET` or `AWS_DEFAULT_REGION` is missing, the system will fallback to generating URLs as if the media were in **local public storage** (`/storage/reviews/...`). This is a safety fallback and usually indicates a misconfiguration if S3 is the intended primary storage for media.
   - Requires proper AWS configuration in `.env` file. For example:
     ```
     AWS_ACCESS_KEY_ID=your-key
     AWS_SECRET_ACCESS_KEY=your-secret
     AWS_DEFAULT_REGION=your-region
     AWS_BUCKET=your-bucket
     AWS_URL=https://yourcloudfrontdomain.cloudfront.net (Optional: For CloudFront URLs)
     ```

To change the storage location, update the `FILESYSTEM_DISK` value in your `.env` file.

### CloudFront Cache Invalidation

When using CloudFront CDN with S3 storage, the API automatically handles cache invalidation when reviews with media are deleted. This ensures that media files are properly removed from the CDN cache.

#### API Response Caching

The service also supports caching API responses in CloudFront. When reviews are created, updated, or deleted, the service automatically invalidates the relevant API cache paths to ensure that clients always receive the most up-to-date data.

The following cache invalidations occur automatically:

- When a review is created: Invalidates the product reviews API cache
- When a review is deleted: Invalidates both the specific review's API cache and the product reviews API cache
- When a review's publication status is updated: Invalidates both the specific review's API cache and the product reviews API cache
- When a review is translated: Invalidates the specific review's API cache

#### Configuration

To enable CloudFront cache invalidation, add the following to your `.env` file:

```
CLOUDFRONT_DISTRIBUTION_ID=your_distribution_id
CLOUDFRONT_KEY=your_aws_key                 # Optional, defaults to AWS_ACCESS_KEY_ID
CLOUDFRONT_SECRET=your_aws_secret           # Optional, defaults to AWS_SECRET_ACCESS_KEY
CLOUDFRONT_REGION=us-east-1                 # Optional, defaults to AWS_DEFAULT_REGION
```

#### Manual Invalidation

You can manually invalidate the CloudFront cache using the command line:

```bash
# Invalidate all review media and API responses
php artisan cloudfront:invalidate --all

# Invalidate only media files
php artisan cloudfront:invalidate --media

# Invalidate only API responses
php artisan cloudfront:invalidate --api

# Invalidate media and API responses for a specific review
php artisan cloudfront:invalidate --review=review_id

# Invalidate API responses for a specific product
php artisan cloudfront:invalidate --product=product_id

# Invalidate specific paths
php artisan cloudfront:invalidate /reviews/review_id/image1.jpg /api/products/product_id/reviews
``` 