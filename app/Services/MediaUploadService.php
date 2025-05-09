<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Class MediaUploadService
 * 
 * Handles the uploading of media files (images, videos) associated with reviews.
 * It supports different storage disks (local, public, S3) and generates appropriate URLs.
 */
class MediaUploadService
{
    /**
     * The storage disk to use for media files, determined by `filesystems.default` config.
     *
     * @var string
     */
    protected string $disk; // Added type hint

    /**
     * Create a new media upload service instance.
     * Initializes the storage disk based on the application's default configuration.
     */
    public function __construct()
    {
        // Use the default disk from filesystems.php configuration
        $this->disk = config('filesystems.default');
    }

    /**
     * Upload a media file to the configured disk and return its metadata.
     *
     * @param UploadedFile $file The uploaded file instance.
     * @param string $reviewId The ID of the review this media belongs to.
     * @return array An array containing media metadata (id, type, path, url).
     */
    public function uploadMedia(UploadedFile $file, string $reviewId): array
    {
        // Generate a unique ID for the media item
        $mediaId = 'media-' . Str::random(8);
        $extension = $file->getClientOriginalExtension();
        
        // Determine if the media is an image or video based on extension
        $type = $this->getMediaType($extension);
        
        // Construct the storage path for the media file
        $path = "reviews/{$reviewId}/{$mediaId}.{$extension}";

        // Store the file on the configured disk (e.g., local, public, s3)
        $file->storeAs('', $path, ['disk' => $this->disk]);

        // Generate the publicly accessible URL for the stored file
        $url = $this->getFileUrl($path);

        // Return structured media metadata
        return [
            'id' => $mediaId,
            'type' => $type,
            'path' => $path,
            'url' => $url,
        ];
    }

    /**
     * Get the publicly accessible URL for a file stored on one of the configured disks.
     *
     * Handles URL generation logic for 'public', 's3' (with CloudFront/AWS_URL priority),
     * and 'local' disks. Provides a generic fallback for other disks.
     *
     * @param string $path The storage path of the file.
     * @return string The generated URL.
     */
    private function getFileUrl(string $path): string
    {
        // For 'public' disk, generate a URL using Laravel's asset helper (points to symbolic link)
        if ($this->disk === 'public') {
            return asset('storage/' . $path);
        }

        // For 's3' disk, prioritize CloudFront URL if AWS_URL is configured,
        // otherwise, generate a direct S3 bucket URL.
        if ($this->disk === 's3') {
            $cloudFrontUrl = config('filesystems.disks.s3.url'); // AWS_URL from .env
            if (!empty($cloudFrontUrl)) {
                // AWS_URL is set (expected to be CloudFront domain), use Storage facade for URL
                return app('filesystem')->disk('s3')->url($path);
            } else {
                // AWS_URL not set, construct a direct S3 URL
                $bucket = config('filesystems.disks.s3.bucket');
                $region = config('filesystems.disks.s3.region');
                
                if ($bucket && $region) {
                    return "https://{$bucket}.s3.{$region}.amazonaws.com/{$path}";
                } else {
                    // Fallback if S3 bucket/region isn't fully configured: assume local public storage for URL generation
                    // This indicates a misconfiguration if S3 was the intended disk for this path.
                    Log::warning("[MediaUploadService] S3 disk used but bucket/region or AWS_URL not fully configured for path: {$path}. Falling back to local asset URL.");
                    return asset('storage/' . $path);
                }
            }
        }

        // For 'local' disk (private storage), generate a URL that the application must handle to serve the file
        if ($this->disk === 'local') {
            // Note: These files are not publicly accessible via direct web server link by default.
            // The route "files/{path}" would need a controller to stream/serve the file.
            return url("files/{$path}");
        }

        // Generic fallback for any other configured disks (e.g., FTP, SFTP if set up)
        // This might need adjustment based on how those disks generate public URLs.
        Log::warning("[MediaUploadService] Unhandled disk type '{$this->disk}' for URL generation for path: {$path}. Using default /storage/ path.");
        return '/storage/' . $path;
    }

    /**
     * Determine the media type (image or video) based on its file extension.
     *
     * @param string $extension The file extension (e.g., 'jpg', 'mp4').
     * @return string 'video' or 'image'.
     */
    private function getMediaType(string $extension): string
    {
        $videoExtensions = ['mp4', 'mov', 'avi', 'mkv', 'webm']; // Added more common video types
        $imageExtensions = ['jpeg', 'png', 'jpg', 'gif', 'bmp', 'webp']; // Added more common image types

        $lowerExtension = strtolower($extension);

        if (in_array($lowerExtension, $videoExtensions)) {
            return 'video';
        }
        // Assuming if not video, it's an image. Could add more specific checks if needed.
        return 'image'; 
    }
}
