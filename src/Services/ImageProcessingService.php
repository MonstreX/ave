<?php

namespace Monstrex\Ave\Services;

use Monstrex\Ave\Media\ImageProcessor;
use Monstrex\Ave\Models\Media as MediaModel;
use Illuminate\Support\Facades\Storage;

/**
 * ImageProcessingService - Handles image processing operations (crop, scale, etc)
 */
class ImageProcessingService
{
    /**
     * Crop image file
     */
    public function cropImage(
        MediaModel $media,
        int $x,
        int $y,
        int $width,
        int $height,
        ?int $maxSize = null,
        ?string $aspectRatio = null
    ): array {
        $cropWidth = $width;
        $cropHeight = $height;
        $aspectRatio = $aspectRatio ?? '';

        // Check if this is Free aspect ratio with maxSize - just scale instead of crop
        $isFreeRatio = empty($aspectRatio);
        $hasMaxSize = isset($maxSize) && $maxSize;

        if ($isFreeRatio && $hasMaxSize) {
            // Free aspect ratio with max size - just scale the cropped area
            $maxSize = (int)$maxSize;
            $maxDimension = max($cropWidth, $cropHeight);

            if ($maxDimension > $maxSize) {
                $scale = $maxSize / $maxDimension;
                $cropWidth = (int)($cropWidth * $scale);
                $cropHeight = (int)($cropHeight * $scale);
            }
        }

        // Get the file path from media disk
        $disk = Storage::disk($media->disk ?: 'public');
        $filePath = $media->path;

        if (!$disk->exists($filePath)) {
            throw new \Exception('Media file not found on disk');
        }

        // Create absolute path for ImageProcessor
        $absolutePath = $disk->path($filePath);

        try {
            // Process the image
            $processor = new ImageProcessor();
            $processor->read($absolutePath);

            // Crop first
            $processor->crop($x, $y, $width, $height);

            // Then resize if needed
            if ($cropWidth != $width || $cropHeight != $height) {
                $processor->scale($cropWidth, $cropHeight);
            }

            $croppedImageData = $processor->encode();

            // Save cropped image back to file
            $disk->put($filePath, $croppedImageData);

            // Update media record with new file size
            $newFileSize = $disk->size($filePath);
            $media->update(['size' => $newFileSize]);
        } catch (\Exception $e) {
            throw new \Exception('Failed to process image: ' . $e->getMessage(), 0, $e);
        }

        return [
            'id' => $media->id,
            'url' => $media->url(),
            'dimensions' => [
                'width' => $cropWidth,
                'height' => $cropHeight,
            ],
            'size' => $newFileSize,
        ];
    }
}
