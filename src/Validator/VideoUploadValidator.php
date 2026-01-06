<?php

namespace App\Validator;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Video Upload Validator
 * 
 * Validates uploaded video files before processing
 */
class VideoUploadValidator
{
    private array $allowedFormats = ['mp4', 'mov', 'mkv'];
    private int $maxFileSize = 5_242_880_000; // 5GB

    public function __construct(array $allowedFormats = [], int $maxFileSize = null)
    {
        if (!empty($allowedFormats)) {
            $this->allowedFormats = $allowedFormats;
        }
        if ($maxFileSize !== null) {
            $this->maxFileSize = $maxFileSize;
        }
    }

    /**
     * Validate uploaded file
     * 
     * @return array Validation errors (empty if valid)
     */
    public function validate(UploadedFile $file): array
    {
        $errors = [];

        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            $errors[] = sprintf(
                'File size exceeds maximum allowed size of %d bytes',
                $this->maxFileSize
            );
        }

        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->allowedFormats)) {
            $errors[] = sprintf(
                'File format not allowed. Allowed formats: %s',
                implode(', ', $this->allowedFormats)
            );
        }

        // Check MIME type
        if (!$this->validateMimeType($file)) {
            $errors[] = 'Invalid video MIME type';
        }

        // Check file is not empty
        if ($file->getSize() === 0) {
            $errors[] = 'File is empty';
        }

        return $errors;
    }

    /**
     * Validate MIME type
     */
    private function validateMimeType(UploadedFile $file): bool
    {
        $validMimeTypes = [
            'video/mp4',
            'video/quicktime',
            'video/x-matroska',
            'application/octet-stream', // Some systems return this
        ];

        $mimeType = $file->getMimeType();
        return in_array($mimeType, $validMimeTypes);
    }

    /**
     * Get allowed formats
     */
    public function getAllowedFormats(): array
    {
        return $this->allowedFormats;
    }

    /**
     * Get max file size
     */
    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }
}
