<?php

namespace Monstrex\Ave\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Monstrex\Ave\Media\Facades\Media;
use Monstrex\Ave\Models\Media as MediaModel;

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
            \Log::debug('[MediaController] Upload request received', [
                'has_image' => $request->hasFile('image'),
                'has_files' => $request->hasFile('files'),
                'model_type' => $request->input('model_type'),
                'model_id' => $request->input('model_id'),
                'collection' => $request->input('collection'),
                'all_inputs' => $request->except(['image', 'files']),
            ]);

            $request->validate([
                'files.*' => 'nullable|file|max:10240',
                'image' => 'nullable|image|max:5120',
                'model_type' => 'nullable|string',
                'model_id' => 'nullable|integer',
                'collection' => 'nullable|string|max:255',
            ]);

            \Log::debug('[MediaController] Validation passed');

            // Single image upload (RichEditor)
            if ($request->hasFile('image')) {
                \Log::debug('[MediaController] Single image upload');
                return $this->uploadSingleImage($request);
            }

            // Multiple files upload (Media field)
            \Log::debug('[MediaController] Multiple files upload');
            return $this->uploadMultiple($request);

        } catch (ValidationException $e) {
            \Log::error('[MediaController] Validation error', [
                'errors' => $e->validator->errors()->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('[MediaController] Upload error', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Also try to get any previous exception
            if ($e->getPrevious()) {
                \Log::error('[MediaController] Previous exception', [
                    'error' => $e->getPrevious()->getMessage(),
                    'trace' => $e->getPrevious()->getTraceAsString(),
                ]);
            }

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

            \Log::debug('[uploadSingleImage] Processing', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'model_class' => $modelClass,
                'model_id' => $modelId,
                'collection' => $collection,
            ]);

            // If model context provided, bind to model
            if ($modelClass && $modelId) {
                \Log::debug('[uploadSingleImage] Binding to model');

                // Load model
                if (!class_exists($modelClass)) {
                    \Log::error('[uploadSingleImage] Model class not found', ['model_class' => $modelClass]);
                    return response()->json([
                        'success' => false,
                        'message' => "Model class not found: {$modelClass}",
                    ], 400);
                }

                $model = $modelClass::find($modelId);
                if (!$model) {
                    \Log::error('[uploadSingleImage] Model record not found', ['model_class' => $modelClass, 'model_id' => $modelId]);
                    return response()->json([
                        'success' => false,
                        'message' => "Model record not found: {$modelClass}#{$modelId}",
                    ], 404);
                }

                \Log::debug('[uploadSingleImage] Creating media with model binding');

                // Upload and bind to model
                $mediaCollection = Media::add($file)
                    ->model($model)
                    ->collection($collection ?: 'default')
                    ->disk('public')
                    ->create();
            } else {
                \Log::debug('[uploadSingleImage] Creating media without model binding (create mode)');

                // For create forms: save to temporary collection
                // These will be migrated to actual model+collection when form is saved
                $tempCollection = '__pending_' . ($collection ?: 'default');
                \Log::debug('[uploadSingleImage] Using temporary collection', ['temp_collection' => $tempCollection]);

                try {
                    // Upload to temporary collection without model binding
                    \Log::debug('[uploadSingleImage] Calling Media::add()', ['file' => $file->getClientOriginalName()]);

                    $mediaBuilder = Media::add($file);
                    \Log::debug('[uploadSingleImage] Media::add() returned');

                    $mediaBuilder = $mediaBuilder->collection($tempCollection);
                    \Log::debug('[uploadSingleImage] Temporary collection set', ['collection' => $tempCollection]);

                    $mediaBuilder = $mediaBuilder->disk('public');
                    \Log::debug('[uploadSingleImage] Disk set to public');

                    $mediaCollection = $mediaBuilder->create();
                    \Log::debug('[uploadSingleImage] Media created in temporary collection');
                } catch (\Exception $e) {
                    \Log::error('[uploadSingleImage] Error during media creation', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    throw $e;
                }
            }

            if ($mediaCollection->isEmpty()) {
                \Log::error('[uploadSingleImage] Media collection is empty after creation');
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create media entry',
                ], 500);
            }

            $media = $mediaCollection->first();
            \Log::debug('[uploadSingleImage] Media created successfully', ['media_id' => $media->id, 'url' => $media->url()]);

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
                    if ($model) {
                        // Bind to model if available
                        $mediaCollection = Media::add($file)
                            ->model($model)
                            ->collection($collection ?: 'default')
                            ->disk('public')
                            ->create();
                    } else {
                        // Upload without model binding
                        $mediaCollection = Media::add($file)
                            ->collection($collection ?: 'default')
                            ->disk('public')
                            ->create();
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
}
