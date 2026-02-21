<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Upload Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all upload-related configuration for the application.
    | You can customize file size limits, allowed MIME types, and S3 settings
    |
    */

    /**
     * Maximum file size in bytes (5MB)
     */
    'max_file_size' => env('UPLOAD_MAX_FILE_SIZE', 5 * 1024 * 1024),

    /**
     * Allowed image MIME types
     */
    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/jpg',
        'image/gif',
        'image/webp',
    ],

    /**
     * Default S3 placeholder values
     */
    's3_placeholder_key' => 'your-access-key-id',
    's3_placeholder_secret' => 'your-secret-access-key',

    /**
     * Default storage disk when S3 is not configured
     */
    'fallback_disk' => env('UPLOAD_FALLBACK_DISK', 'public'),

];
