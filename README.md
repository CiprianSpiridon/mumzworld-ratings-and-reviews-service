# Mumzworld Ratings and Reviews Service

This service provides API endpoints for managing product ratings and reviews, including support for media uploads (images and videos).

## Features

- Create, retrieve, update, and delete product reviews
- Filter reviews by product, user, country, language, and publication status
- Support for multilingual reviews (English and Arabic)
- Automatic translation of reviews between supported languages
- Media upload functionality for attaching images and videos to reviews
- Publication status management (pending, published, rejected)
- Configurable storage options (local, public, S3)
- CloudFront cache invalidation for media files and API responses

## API Documentation

For detailed API documentation, see [docs/API.md](docs/API.md).

### OpenAPI Specification

The API is documented using the OpenAPI 3.1 specification. You can find the specification file at [docs/openapi.yaml](docs/openapi.yaml).

You can use this specification with tools like:
- [Swagger UI](https://swagger.io/tools/swagger-ui/) for interactive API documentation
- [Postman](https://www.postman.com/) for API testing (import the OpenAPI spec)
- [OpenAPI Generator](https://openapi-generator.tech/) for generating client libraries

### Postman Collection

A Postman collection is available for testing the API endpoints. See [docs/postman/README.md](docs/postman/README.md) for instructions on how to use it.

### Media Upload

The service supports uploading media files (images and videos) with reviews:

- Supported file types: jpeg, png, jpg, gif, mp4, mov, avi
- Maximum file size: 10MB per file
- Files are stored using the default filesystem disk configured in `config/filesystems.php`
- Storage location can be changed by setting the `FILESYSTEM_DISK` environment variable

#### Storage Options

1. **Local Storage** (default: 'local')
   - Files are stored in `storage/app/private/reviews/{reviewId}/{mediaId}.{extension}`
   - Access is controlled by the application

2. **Public Storage** (set `FILESYSTEM_DISK=public`)
   - Files are stored in `storage/app/public/reviews/{reviewId}/{mediaId}.{extension}`
   - Public URL: `/storage/reviews/{reviewId}/{mediaId}.{extension}`
   - Requires running `php artisan storage:link` to create the symbolic link

3. **S3 Storage** (set `FILESYSTEM_DISK=s3`)
   - Files are stored in Amazon S3
   - Requires proper AWS configuration in `.env` file

To make the storage directory publicly accessible, run:

```bash
php artisan storage:link
```

### CloudFront Cache Invalidation

The service includes automatic CloudFront cache invalidation for media files and API responses when reviews are created, updated, or deleted. This ensures that both media files and API responses are properly refreshed in the CDN cache when the underlying data changes.

#### Configuration

To use the CloudFront cache invalidation feature, add the following to your `.env` file:

```
CLOUDFRONT_DISTRIBUTION_ID=your_distribution_id
CLOUDFRONT_KEY=your_aws_key                 # Optional, defaults to AWS_ACCESS_KEY_ID
CLOUDFRONT_SECRET=your_aws_secret           # Optional, defaults to AWS_SECRET_ACCESS_KEY
CLOUDFRONT_REGION=us-east-1                 # Optional, defaults to AWS_DEFAULT_REGION
```

#### Manual Invalidation

You can manually invalidate the CloudFront cache using the following command:

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

# Run synchronously (wait for completion)
php artisan cloudfront:invalidate --all --sync
```

## Translation Service

The service includes automatic translation of reviews between supported languages (currently English and Arabic):

- Reviews are automatically translated when created
- Missing translations can be generated using the command:
  ```bash
  php artisan reviews:translate --limit=100 --status=published
  ```
- On-demand translation is available through the API endpoint:
  ```
  GET /api/reviews/{id}/translate?language=ar
  ```

### Translation API Endpoint

The translation endpoint allows users to request a specific translation:

- **URL**: `/api/reviews/{id}/translate`
- **Method**: GET
- **URL Params**: 
  - Required: `id=[string]` (Review ID)
- **Query Params**:
  - Required: `language=[string]` (Target language code, either 'en' or 'ar')
- **Success Response**:
  - Code: 200
  - Content: The review resource with the requested translation
- **Error Responses**:
  - Code: 404 (Not Found) - If the review doesn't exist
  - Code: 422 (Unprocessable Entity) - If the language parameter is invalid
  - Code: 500 (Internal Server Error) - If translation fails

If the translation already exists, it will be returned immediately. If not, the service will translate the content and then return it.

### Configuration

To use the translation service, you need to set up Google Cloud Translation API:

1. Obtain a Google Cloud API key with access to the Translation API
2. Add the following to your `.env` file:
   ```
   GOOGLE_TRANSLATE_API_KEY=your_api_key
   GOOGLE_TRANSLATE_ENDPOINT=https://translation.googleapis.com/language/translate/v2
   ```

### Scheduled Translation

The service is configured to automatically translate published reviews daily. You can modify this schedule in `app/Console/Kernel.php`.

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Set up environment variables:
   ```bash
   cp .env.example .env
   ```
4. Generate application key:
   ```bash
   php artisan key:generate
   ```
5. Create the symbolic link for public storage:
   ```bash
   php artisan storage:link
   ```
6. Run the database migrations and seeders:
   ```bash
   php artisan migrate --seed
   ```

## Seeding Data

The database seeder creates a variety of reviews with placeholder media:

```bash
php artisan db:seed --class=RatingAndReviewSeeder
```

This will:
- Create reviews for 5,000 products, with each product having a random number of reviews (1-20)
- Create 10 published reviews with images for a specific test product (ID: 12345678-1234-1234-1234-123456789012)
- Create 5 published reviews with videos for the same test product
- Create 5 reviews with multiple media items (mix of image and video placeholders)
- Create reviews with different publication statuses (pending, published, rejected)

The seeder uses placeholder URLs from various image and video services:
- Images: Lorem Picsum, Placehold.co, LoremFlickr, Unsplash
- Videos: YouTube video links

No actual files are created or stored during seeding, only metadata with URLs.

## Testing

Run the test suite:

```bash
php artisan test
```
