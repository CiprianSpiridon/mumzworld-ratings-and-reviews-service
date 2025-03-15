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
- `per_page` (optional): Number of reviews per page

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
  "links": {
    "first": "string",
    "last": "string",
    "prev": "string|null",
    "next": "string|null"
  },
  "meta": {
    "current_page": "integer",
    "from": "integer",
    "last_page": "integer",
    "path": "string",
    "per_page": "integer",
    "to": "integer",
    "total": "integer"
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

**Path Parameters:**

- `id` (required): The review ID

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
   - Files are stored in Amazon S3
   - Requires proper AWS configuration in `.env` file:
     ```
     AWS_ACCESS_KEY_ID=your-key
     AWS_SECRET_ACCESS_KEY=your-secret
     AWS_DEFAULT_REGION=your-region
     AWS_BUCKET=your-bucket
     ```

To change the storage location, update the `FILESYSTEM_DISK` value in your `.env` file. 