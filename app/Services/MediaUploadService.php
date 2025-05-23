<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUploadService
{
    /**
     * The storage disk to use for media files.
     *
     * @var string
     */
    protected $disk;

    /**
     * Create a new media upload service instance.
     */
    public function __construct()
    {
        // Use the default disk from filesystems.php configuration
        $this->disk = config('filesystems.default');
    }

    /**
     * Upload a media file and return its metadata.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $reviewId
     * @return array
     */
    public function uploadMedia(UploadedFile $file, string $reviewId): array
    {
        $mediaId = 'media-' . Str::random(8);
        $extension = $file->getClientOriginalExtension();
        $type = $this->getMediaType($extension);
        $path = "reviews/{$reviewId}/{$mediaId}.{$extension}";

        // Store the file on the configured disk
        $file->storeAs('', $path, ['disk' => $this->disk]);

        // Return the media metadata with URL
        $url = $this->getFileUrl($path);

        return [
            'id' => $mediaId,
            'type' => $type,
            'path' => $path,
            'url' => $url,
        ];
    }

    /**
     * Get the URL for a file.
     *
     * @param string $path
     * @return string
     */
    private function getFileUrl(string $path): string
    {
        // For public disk, use the asset helper with the storage path
        if ($this->disk === 'public') {
            return asset('storage/' . $path);
        }

        // For S3, prioritize CloudFront URL if AWS_URL is set, otherwise use direct S3 URL
        if ($this->disk === 's3') {
            $cloudFrontUrl = config('filesystems.disks.s3.url');
            if (!empty($cloudFrontUrl)) {
                // AWS_URL is set (presumably to a CloudFront domain), use app('filesystem')->disk()->url()
                return app('filesystem')->disk('s3')->url($path);
            } else {
                // AWS_URL is not set, construct direct S3 URL as a fallback
                $bucket = config('filesystems.disks.s3.bucket');
                $region = config('filesystems.disks.s3.region');
                // Ensure bucket and region are fetched correctly
                if ($bucket && $region) {
                    return "https://{$bucket}.s3.{$region}.amazonaws.com/{$path}";
                } else {
                    // Fallback: if S3 bucket/region not configured (and AWS_URL not set), assume local public storage for URL
                    return asset('storage/' . $path);
                }
            }
        }

        // For local disk, return a path that can be served by the application
        if ($this->disk === 'local') {
            // Note: local disk files may not be publicly accessible by default
            return url("files/{$path}");
        }

        // For other disks, return a relative path or implement as needed
        return '/storage/' . $path;
    }

    /**
     * Determine the media type based on file extension.
     *
     * @param string $extension
     * @return string
     */
    private function getMediaType(string $extension): string
    {
        $videoExtensions = ['mp4', 'mov', 'avi'];

        return in_array(strtolower($extension), $videoExtensions) ? 'video' : 'image';
    }
}
