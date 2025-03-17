# Mumzworld Ratings and Reviews API - Postman Collection

This directory contains a Postman collection for testing the Mumzworld Ratings and Reviews API.

## Getting Started

1. Download and install [Postman](https://www.postman.com/downloads/)
2. Import the collection file `mumzworld-ratings-reviews-api.json` into Postman
3. Set up your environment variable for `base_url` (default is `http://localhost:3000`)

## Available Requests

The collection includes the following requests:

### Create Review
- **Method**: POST
- **Endpoint**: `/api/reviews`
- **Description**: Creates a new rating and review for a product
- **Body**: Form data with user_id, product_id, rating, original_language, review text, country, and optional media files

### Create Review with Arabic
- **Method**: POST
- **Endpoint**: `/api/reviews`
- **Description**: Creates a new rating and review in Arabic
- **Body**: Similar to Create Review but with original_language set to "ar" and review_ar field

### Get Product Reviews
- **Method**: GET
- **Endpoint**: `/api/products/:id/reviews`
- **Description**: Retrieves reviews for a specific product with filters
- **Parameters**: 
  - Path: product ID
  - Query: country, language, publication_status, per_page

### Get Product Reviews (All)
- **Method**: GET
- **Endpoint**: `/api/products/:id/reviews`
- **Description**: Retrieves all published reviews for a specific product
- **Parameters**: 
  - Path: product ID

### Delete Review
- **Method**: DELETE
- **Endpoint**: `/api/reviews/:id`
- **Description**: Deletes a specific review
- **Parameters**: 
  - Path: review ID

### Update Publication Status
- **Method**: PUT
- **Endpoint**: `/api/reviews/:id/publication`
- **Description**: Updates the publication status of a review
- **Parameters**: 
  - Path: review ID
- **Body**: JSON with publication_status field

### Get Translated Review
- **Method**: GET
- **Endpoint**: `/api/reviews/:id/translate`
- **Description**: Gets a review with translation to the requested language
- **Parameters**: 
  - Path: review ID
  - Query: language (either 'en' or 'ar')
- **Notes**: If the translation doesn't exist, it will be created on-demand

### Create Review with Media
- **Method**: POST
- **Endpoint**: `/api/reviews`
- **Description**: Creates a new rating and review with multiple media files
- **Body**: Form data with user_id, product_id, rating, original_language, review text, country, and multiple media_files[] entries

## Testing Media Upload

To test media upload functionality:

1. Open the "Create Review with Media" request
2. Click on the "media_files[]" field and select image or video files to upload
3. You can add multiple files by clicking the "Add more" button
4. Send the request and check the response for the media metadata

## Environment Variables

The collection uses the following environment variables:

- `base_url`: The base URL of your API (default: http://localhost:3000)

You can create a new environment in Postman and set these variables according to your development, staging, or production environments. 