<?php

namespace Monstrex\Ave\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Monstrex\Ave\Media\Facades\Media;
use Monstrex\Ave\Media\ImageProcessor;
use Monstrex\Ave\Support\StorageProfile;

/**
 * MediaUploadService - Handles all media and file upload operations
 */
class MediaUploadService
{
    /**
     * Upload single image (RichEditor)
     */
    public function uploadSingleImage(
        UploadedFile $file,
        ?string $modelClass = null,
        ?int $modelId = null,
        ?string $collection = null,
        ?string $pathStrategy = null,
        ?string $pathPrefix = null
    ) {
        // Scale image if needed before uploading
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $this->processImageBeforeUpload($file);
        }

        $defaultDisk = StorageProfile::make()->disk();

        // If model context provided, bind to model
        if ($modelClass && $modelId) {
            // Load model
            if (!class_exists($modelClass)) {
                throw new \Exception("Model class not found: {$modelClass}");
            }

            $model = $modelClass::find($modelId);
            if (!$model) {
                throw new \Exception("Model record not found: {$modelClass}#{$modelId}");
            }

            // Upload and bind to model
            $mediaBuilder = Media::add($file)
                ->model($model)
                ->collection($collection ?: 'default')
                ->disk($defaultDisk);

            // Apply path strategy if provided
            if ($pathStrategy) {
                $mediaBuilder->pathStrategy($pathStrategy);
            }

            if ($pathPrefix) {
                $mediaBuilder->pathPrefix($pathPrefix);
            }

            $mediaCollection = $mediaBuilder->create();
        } else {
            // For create forms: save to temporary collection
            // These will be migrated to actual model+collection when form is saved
            $tempCollection = '__pending_' . ($collection ?: 'default');

            $mediaBuilder = Media::add($file)
                ->collection($tempCollection)
                ->disk($defaultDisk);

            // Apply path strategy if provided
            if ($pathStrategy) {
                $mediaBuilder->pathStrategy($pathStrategy);
            }

            if ($pathPrefix) {
                $mediaBuilder->pathPrefix($pathPrefix);
            }

            $mediaCollection = $mediaBuilder->create();
        }

        if ($mediaCollection->isEmpty()) {
            throw new \Exception('Failed to create media entry');
        }

        return $mediaCollection->first();
    }

    /**
     * Upload multiple files (Media field)
     */
    public function uploadMultiple(
        array $files,
        ?string $modelClass = null,
        ?int $modelId = null,
        string $collection = 'default',
        ?string $pathStrategy = null,
        ?string $customPath = null,
        ?string $pathPrefix = null
    ): array {
        $defaultDisk = StorageProfile::make()->disk();

        // Load model if context provided
        $model = null;
        if ($modelClass && $modelId) {
            if (!class_exists($modelClass)) {
                throw new \Exception("Model class not found: {$modelClass}");
            }

            $model = $modelClass::find($modelId);
            if (!$model) {
                throw new \Exception("Model record not found: {$modelClass}#{$modelId}");
            }
        }

        $uploadedMedia = [];

        // Upload each file
        foreach ($files as $file) {
            try {
                // Scale image if needed before uploading
                if ($file && str_starts_with($file->getMimeType(), 'image/')) {
                    $this->processImageBeforeUpload($file);
                }

                $mediaBuilder = Media::add($file)
                    ->collection($collection)
                    ->disk($defaultDisk);

                // Apply custom path if provided (from pathGenerator callback)
                if ($customPath) {
                    $mediaBuilder->directPath($customPath);
                } elseif ($pathStrategy) {
                    // Apply path strategy if provided
                    $mediaBuilder->pathStrategy($pathStrategy);
                }

                if ($pathPrefix) {
                    $mediaBuilder->pathPrefix($pathPrefix);
                }

                if ($model) {
                    // Bind to model if available
                    $mediaCollection = $mediaBuilder
                        ->model($model)
                        ->create();
                } else {
                    // Upload without model binding
                    $mediaCollection = $mediaBuilder->create();
                }

                if ($mediaCollection->isEmpty()) {
                    throw new \Exception('Media creation returned empty collection');
                }

                $media = $mediaCollection->first();

                $uploadedMedia[] = [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'url' => $media->url(),
                ];

            } catch (\Exception $e) {
                // Log error but continue with next file
                logger()->error('Media upload failed', [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (empty($uploadedMedia)) {
            throw new \Exception('No files were uploaded successfully');
        }

        return $uploadedMedia;
    }

    /**
     * Upload simple file (for File field)
     */
    public function uploadFile(
        UploadedFile $file,
        ?string $filenameStrategy = null,
        ?string $pathStrategy = null,
        ?string $customPath = null,
        ?string $modelType = null,
        ?int $modelId = null,
        ?string $locale = null,
        ?string $pathPrefix = null
    ): string {
        $profile = StorageProfile::make()->with(array_filter([
            'path.strategy' => $pathStrategy,
            'filename.strategy' => $filenameStrategy,
            'filename.locale' => $locale,
            'path_prefix' => $pathPrefix,
        ], static fn ($value) => $value !== null));

        // Try to resolve model if model_type provided
        $model = null;
        if ($modelType) {
            if (class_exists($modelType) && $modelId) {
                try {
                    $model = app($modelType)->find($modelId);
                } catch (\Exception $e) {
                    // Model not found, proceed without model context
                }
            }
        }

        $path = $profile->buildPath([
            'customPath' => $customPath,
            'model' => $model,
            'recordId' => $modelId,
            'pathPrefix' => $pathPrefix,
        ]);

        $disk = $profile->disk();
        $filesystem = Storage::disk($disk);

        $fileName = $profile->generateFilename(
            $file->getClientOriginalName(),
            [
                'existsCallback' => fn(string $candidate) => $filesystem->exists($path . '/' . $candidate),
            ]
        );

        $storedPath = $file->storeAs($path, $fileName, $disk);

        return '/storage/' . $storedPath;
    }

    /**
     * Process image before upload (resize if needed)
     */
    protected function processImageBeforeUpload(UploadedFile $file): void
    {
        // Get max image size from storage profile
        $maxSize = StorageProfile::make()->imageMaxSize();

        if (!$maxSize || $maxSize <= 0) {
            return;
        }

        try {
            $absolutePath = $file->getRealPath();
            $processor = new ImageProcessor();

            // Read image and get dimensions
            $processor->read($absolutePath);
            $dimensions = $processor->getDimensions();
            $width = $dimensions['width'];
            $height = $dimensions['height'];

            // Check if scaling is needed
            $maxDimension = max($width, $height);
            if ($maxDimension <= $maxSize) {
                return;
            }

            // Calculate new dimensions maintaining aspect ratio
            $aspectRatio = $width / $height;
            if ($width > $height) {
                // Width is limiting factor
                $newWidth = $maxSize;
                $newHeight = (int)($maxSize / $aspectRatio);
            } else {
                // Height is limiting factor
                $newHeight = $maxSize;
                $newWidth = (int)($maxSize * $aspectRatio);
            }

            // Resize image and encode
            $scaledImageData = $processor
                ->scale($newWidth, $newHeight)
                ->encode();

            // Write back to temporary uploaded file
            file_put_contents($absolutePath, $scaledImageData);

        } catch (\Exception $e) {
            // Continue without scaling if it fails - don't break upload
        }
    }
}
