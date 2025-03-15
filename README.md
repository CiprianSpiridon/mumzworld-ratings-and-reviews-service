# Mumzworld Ratings and Reviews Service

This service provides API endpoints for managing product ratings and reviews, including support for media uploads (images and videos).

## Features

- Create, retrieve, update, and delete product reviews
- Filter reviews by product, user, country, language, and publication status
- Support for multilingual reviews (English and Arabic)
- Media upload functionality for attaching images and videos to reviews
- Publication status management (pending, published, rejected)
- Configurable storage options (local, public, S3)

## API Documentation

For detailed API documentation, see [docs/API.md](docs/API.md).

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
php artisan db:seed
```

This will:
- Create basic reviews without media
- Create reviews with single images (using placeholder image URLs)
- Create reviews with single videos (using placeholder video URLs)
- Create reviews with multiple media items (mix of image and video placeholders)
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

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
