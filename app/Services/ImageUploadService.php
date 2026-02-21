<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    /**
     * Get the appropriate disk for image storage
     */
    private function getDisk(): string
    {
        $s3Key = config('filesystems.disks.s3.key');
        $s3Secret = config('filesystems.disks.s3.secret');

        $s3Configured = $s3Key
            && $s3Key !== config('upload.s3_placeholder_key')
            && $s3Secret
            && $s3Secret !== config('upload.s3_placeholder_secret');

        return $s3Configured ? 's3' : config('upload.fallback_disk');
    }

    /**
     * Upload an image to S3 or local disk
     */
    public function uploadProductImage(UploadedFile $file, int $productId): string
    {
        $this->validateFile($file);

        $filename = $this->generateFilename($file);
        $directory = "products/{$productId}";
        $disk = $this->getDisk();

        $path = Storage::disk($disk)->putFileAs(
            $directory,
            $file,
            $filename,
            'public',
            ['visibility' => 'public']
        );

        return Storage::disk($disk)->url($path);
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file): void
    {
        if (!in_array($file->getMimeType(), config('upload.allowed_mime_types'))) {
            throw new \InvalidArgumentException(
                'Invalid file type. Allowed types: ' . implode(', ', config('upload.allowed_mime_types'))
            );
        }

        if ($file->getSize() > config('upload.max_file_size')) {
            throw new \InvalidArgumentException(
                "File size exceeds " . config('upload.max_file_size') . " bytes limit"
            );
        }
    }

    /**
     * Generate unique filename
     */
    private function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        return Str::uuid() . ".{$extension}";
    }

    /**
     * Delete an image from S3 or local disk
     */
    public function deleteProductImage(string $imageUrl): bool
    {
        try {
            $path = $this->extractPathFromUrl($imageUrl);
            $disk = $this->getDisk();

            return Storage::disk($disk)->delete($path);
        } catch (\Exception $e) {
            \Log::error('Failed to delete image', [
                'url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Extract file path from URL
     */
    private function extractPathFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $path = ltrim($path, '/');
        return preg_replace('#^storage/#', '', $path);
    }
}
