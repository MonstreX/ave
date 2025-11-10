<?php

namespace Monstrex\Ave\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Monstrex\Ave\Media\Facades\Media;
use Monstrex\Ave\Models\Media as MediaModel;
use Monstrex\Ave\Media\ImageProcessor;

/**
 * MediaController - Unified media upload/management endpoint
 *
 * Handles:
 * - RichEditor image uploads (single image with model binding)
 * - Media field uploads (multiple files with model binding)
 * - Media deletion
 * - Media reordering
 * - Media properties update
 */
class MediaController extends Controller
{
    /**
     * Upload media files
     *
     * Request params:
     * - files[] or image: file upload
     * - model_type: Full class name (App\Models\Article)
     * - model_id: Model record ID
     * - collection: Collection name (field name or custom)
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'files.*' => 'nullable|file|max:10240',
                'image' => 'nullable|image|max:5120',
                'model_type' => 'nullable|string',
                'model_id' => 'nullable|integer',
                'collection' => 'nullable|string|max:255',
                'pathStrategy' => 'nullable|in:flat,dated',
                'customPath' => 'nullable|string|max:255',
            ]);

            // Single image upload (RichEditor)
            if ($request->hasFile('image')) {
                return $this->uploadSingleImage($request);
            }

            // Multiple files upload (Media field)
            return $this->uploadMultiple($request);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload single image (RichEditor)
     */
    protected function uploadSingleImage(Request $request): JsonResponse
    {
        try {
            $file = $request->file('image');
            $modelClass = $request->input('model_type');
            $modelId = $request->input('model_id');
            $collection = $request->input('collection');
            $pathStrategy = $request->input('pathStrategy');

            // Scale image if needed before uploading
            if ($file && str_starts_with($file->getMimeType(), 'image/')) {
                $this->processImageBeforeUpload($file);
            }

            // If model context provided, bind to model
            if ($modelClass && $modelId) {
                // Load model
                if (!class_exists($modelClass)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Model class not found: {$modelClass}",
                    ], 400);
                }

                $model = $modelClass::find($modelId);
                if (!$model) {
                    return response()->json([
                        'success' => false,
                        'message' => "Model record not found: {$modelClass}#{$modelId}",
                    ], 404);
                }

                // Upload and bind to model
                $mediaBuilder = Media::add($file)
                    ->model($model)
                    ->collection($collection ?: 'default')
                    ->disk('public');

                // Apply path strategy if provided
                if ($pathStrategy) {
                    $mediaBuilder->pathStrategy($pathStrategy);
                }

                $mediaCollection = $mediaBuilder->create();
            } else {
                // For create forms: save to temporary collection
                // These will be migrated to actual model+collection when form is saved
                $tempCollection = '__pending_' . ($collection ?: 'default');

                try {
                    $mediaBuilder = Media::add($file)
                        ->collection($tempCollection)
                        ->disk('public');

                    // Apply path strategy if provided
                    if ($pathStrategy) {
                        $mediaBuilder->pathStrategy($pathStrategy);
                    }

                    $mediaCollection = $mediaBuilder->create();
                } catch (\Exception $e) {
                    throw $e;
                }
            }

            if ($mediaCollection->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create media entry',
                ], 500);
            }

            $media = $mediaCollection->first();

            // Jodit response format
            return response()->json([
                'success' => true,
                'data' => [
                    'url' => $media->url(),
                    'id' => $media->id,
                    'filename' => $media->file_name,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload multiple files (Media field)
     */
    protected function uploadMultiple(Request $request): JsonResponse
    {
        try {
            if (!$request->hasFile('files') || empty($request->file('files'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'No files provided',
                ], 400);
            }

            $modelClass = $request->input('model_type');
            $modelId = $request->input('model_id');
            $collection = $request->input('collection');
            $pathStrategy = $request->input('pathStrategy');
            $customPath = $request->input('customPath');

            // Load model if context provided
            $model = null;
            if ($modelClass && $modelId) {
                if (!class_exists($modelClass)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Model class not found: {$modelClass}",
                    ], 400);
                }

                $model = $modelClass::find($modelId);
                if (!$model) {
                    return response()->json([
                        'success' => false,
                        'message' => "Model record not found: {$modelClass}#{$modelId}",
                    ], 404);
                }
            }

            $uploadedMedia = [];

            // Upload each file
            foreach ($request->file('files') as $file) {
                try {
                    // Scale image if needed before uploading
                    if ($file && str_starts_with($file->getMimeType(), 'image/')) {
                        $this->processImageBeforeUpload($file);
                    }

                    $mediaBuilder = Media::add($file)
                        ->collection($collection ?: 'default')
                        ->disk('public');

                    // Apply custom path if provided (from pathGenerator callback)
                    if ($customPath) {
                        $mediaBuilder->directPath($customPath);
                    } elseif ($pathStrategy) {
                        // Apply path strategy if provided
                        $mediaBuilder->pathStrategy($pathStrategy);
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
                return response()->json([
                    'success' => false,
                    'message' => 'No files were uploaded successfully',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'media' => $uploadedMedia,
                'count' => count($uploadedMedia),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete media by ID
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $media = MediaModel::findOrFail($id);
            $media->delete();

            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Media not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete media by IDs
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'integer|min:1',
            ]);

            $ids = $request->input('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No IDs provided',
                ], 400);
            }

            $deleted = 0;

            // Delete each media item
            foreach ($ids as $id) {
                try {
                    $media = MediaModel::find($id);
                    if ($media) {
                        $media->delete();
                        $deleted++;
                    }
                } catch (\Exception $e) {
                    // Continue with next item
                }
            }

            return response()->json([
                'success' => true,
                'message' => "{$deleted} file(s) deleted successfully",
                'deleted' => $deleted,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk delete failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete entire media collection (used by Fieldset item removal)
     */
    public function destroyCollection(Request $request): JsonResponse
    {
        $data = $request->validate([
            'collection' => 'required|string',
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
        ]);

        if (!class_exists($data['model_type'])) {
            return response()->json([
                'success' => false,
                'message' => 'Model class not found: ' . $data['model_type'],
            ], 400);
        }

        $deleted = 0;

        MediaModel::query()
            ->where('collection_name', $data['collection'])
            ->where('model_type', $data['model_type'])
            ->where('model_id', $data['model_id'])
            ->chunkById(100, static function ($items) use (&$deleted) {
                foreach ($items as $media) {
                    $media->delete();
                    $deleted++;
                }
            });

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
        ]);
    }

    /**
     * Reorder media items
     */
    public function reorder(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'media' => 'required|array',
                'media.*.id' => 'required|integer',
                'media.*.order' => 'required|integer|min:0',
            ]);

            $mediaItems = $request->input('media', []);
            $updated = 0;

            foreach ($mediaItems as $item) {
                $media = MediaModel::find($item['id']);
                if ($media) {
                    $media->order = $item['order'];
                    $media->save();
                    $updated++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Reordered {$updated} items",
                'updated' => $updated,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reorder failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update media properties
     */
    public function updateProps(Request $request, int $id): JsonResponse
    {
        try {
            $media = MediaModel::findOrFail($id);

            $props = $request->json()->all();
            unset($props['_token'], $props['_method']);

            if (!empty($props)) {
                $currentProps = $media->props ? json_decode($media->props, true) : [];
                $media->props = json_encode(array_merge($currentProps, $props));
                $media->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Properties updated successfully',
                'media' => [
                    'id' => $media->id,
                    'props' => $media->props ? json_decode($media->props, true) : [],
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Media not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crop image file
     *
     * Request body (JSON):
     * - x: int - X coordinate of crop area
     * - y: int - Y coordinate of crop area
     * - width: int - Width of crop area
     * - height: int - Height of crop area
     * - maxWidth: int (optional) - Maximum width constraint
     * - maxHeight: int (optional) - Maximum height constraint
     */
    public function cropImage(Request $request, int $id): JsonResponse
    {
        try {
            $media = MediaModel::findOrFail($id);

            // Validate that media is an image
            if (!in_array($media->mime_type, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only image files can be cropped',
                ], 400);
            }

            // Validate crop parameters
            $data = $request->validate([
                'x' => 'required|integer|min:0',
                'y' => 'required|integer|min:0',
                'width' => 'required|integer|min:1',
                'height' => 'required|integer|min:1',
                'maxSize' => 'nullable|integer|min:1',
                'aspectRatio' => 'nullable|string',
            ]);

            $cropWidth = (int)$data['width'];
            $cropHeight = (int)$data['height'];
            $aspectRatio = $data['aspectRatio'] ?? '';


            // Check if this is Free aspect ratio with maxSize - just scale instead of crop
            $isFreeRatio = empty($aspectRatio);
            $hasMaxSize = isset($data['maxSize']) && $data['maxSize'];

            if ($isFreeRatio && $hasMaxSize) {
                // Free aspect ratio with max size - just scale the cropped area
                $maxSize = (int)$data['maxSize'];
                $maxDimension = max($cropWidth, $cropHeight);

                if ($maxDimension > $maxSize) {
                    $scale = $maxSize / $maxDimension;
                    $cropWidth = (int)($cropWidth * $scale);
                    $cropHeight = (int)($cropHeight * $scale);
                }
            }

            // Get the file path from media disk
            $disk = \Storage::disk($media->disk ?: 'public');
            $filePath = $media->path;

            if (!$disk->exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Media file not found on disk',
                ], 404);
            }

            // Create absolute path for ImageProcessor
            $absolutePath = $disk->path($filePath);

            // Process the image
            $processor = new ImageProcessor();
            $processor->read($absolutePath);

            // Crop first
            $processor->crop((int)$data['x'], (int)$data['y'], (int)$data['width'], (int)$data['height']);

            // Then resize if needed
            if ($cropWidth != (int)$data['width'] || $cropHeight != (int)$data['height']) {
                $processor->scale($cropWidth, $cropHeight);
            }

            $croppedImageData = $processor->encode();

            // Save cropped image back to file
            $disk->put($filePath, $croppedImageData);

            // Update media record with new file size
            $newFileSize = $disk->size($filePath);
            $media->update(['size' => $newFileSize]);

            return response()->json([
                'success' => true,
                'message' => 'Image cropped successfully',
                'media' => [
                    'id' => $media->id,
                    'url' => $media->url(),
                    'dimensions' => [
                        'width' => $cropWidth,
                        'height' => $cropHeight,
                    ],
                    'size' => $newFileSize,
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Media not found',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Crop failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process image before upload (resize if needed)
     */
    protected function processImageBeforeUpload(\Illuminate\Http\UploadedFile $file): void
    {
        // Get max image size from config
        $maxSize = config('ave.media.max_image_size', 2000);

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

    /**
     * Upload simple file (for File field)
     *
     * Request params:
     * - file: single file upload
     * - field: field name (optional)
     * - model_type: optional model class for path generation
     * - model_id: optional model record ID for path generation
     * - pathStrategy: optional path strategy ('flat'|'dated')
     * - filenameStrategy: optional filename strategy ('original'|'transliterate'|'unique')
     * - locale: optional locale for transliterate strategy
     */
    public function uploadFile(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|max:102400', // 100MB max
                'field' => 'nullable|string',
                'model_type' => 'nullable|string',
                'model_id' => 'nullable',
                'pathStrategy' => 'nullable|in:flat,dated',
                'filenameStrategy' => 'nullable|in:original,transliterate,unique',
                'locale' => 'nullable|string',
                'customPath' => 'nullable|string|max:255',
            ]);

            $file = $request->file('file');

            // Get filename strategy
            $filenameStrategy = $request->input('filenameStrategy') ??
                              config('ave.files.filename.strategy', 'transliterate');
            $filenameSeparator = config('ave.files.filename.separator', '-');
            $filenameLocale = $request->input('locale') ?? config('ave.files.filename.locale', 'ru');

            // Get path strategy
            // Get custom path if provided (from pathGenerator callback)
            $customPath = $request->input('customPath');
            $pathStrategy = $request->input('pathStrategy') ??
                           config('ave.files.path.strategy', 'dated');

            // Try to resolve model if model_type provided
            $model = null;
            if ($modelType = $request->input('model_type')) {
                if (class_exists($modelType) && $modelId = $request->input('model_id')) {
                    try {
                        $model = app($modelType)->find($modelId);
                    } catch (\Exception $e) {
                        // Model not found, proceed without model context
                    }
                }
            }

            // Use custom path if provided, otherwise generate it
            if ($customPath) {
                $path = $customPath;
            } else {
                // Generate path using PathGeneratorService
                $path = \Monstrex\Ave\Services\PathGeneratorService::generate([
                    'root' => config('ave.files.root', 'uploads/files'),
                    'strategy' => $pathStrategy,
                    'model' => $model,
                    'recordId' => $request->input('model_id'),
                    'year' => date('Y'),
                    'month' => date('m'),
                ]);
            }
            // Generate filename using FilenameGeneratorService
            $fileName = \Monstrex\Ave\Services\FilenameGeneratorService::generate(
                $file->getClientOriginalName(),
                [
                    'strategy' => $filenameStrategy,
                    'separator' => $filenameSeparator,
                    'locale' => $filenameLocale,
                ]
            );

            // Store file
            $storedPath = $file->storeAs($path, $fileName, config('ave.files.disk', 'public'));

            return response()->json([
                'success' => true,
                'path' => '/storage/' . $storedPath,
                'message' => 'File uploaded successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
