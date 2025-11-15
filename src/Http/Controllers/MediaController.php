<?php

namespace Monstrex\Ave\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Monstrex\Ave\Models\Media as MediaModel;
use Monstrex\Ave\Services\MediaUploadService;
use Monstrex\Ave\Services\ImageProcessingService;
use Monstrex\Ave\Services\MediaManagementService;

/**
 * MediaController - Thin controller layer for media operations
 *
 * Delegates to services:
 * - MediaUploadService: File uploads
 * - ImageProcessingService: Image processing (crop, etc)
 * - MediaManagementService: Media CRUD operations
 */
class MediaController extends Controller
{
    public function __construct(
        protected MediaUploadService $uploadService,
        protected ImageProcessingService $imageService,
        protected MediaManagementService $managementService
    ) {}

    /**
     * Upload media files (RichEditor or Media field)
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
                'pathPrefix' => 'nullable|string|max:255',
            ]);

            if ($request->hasFile('image')) {
                $media = $this->uploadService->uploadSingleImage(
                    $request->file('image'),
                    $request->input('model_type'),
                    $request->input('model_id'),
                    $request->input('collection'),
                    $request->input('pathStrategy'),
                    $request->input('pathPrefix') ? trim($request->input('pathPrefix')) : null
                );

                return response()->json([
                    'success' => true,
                    'data' => [
                        'url' => $media->url(),
                        'id' => $media->id,
                        'filename' => $media->file_name,
                    ],
                ]);
            }

            $uploadedMedia = $this->uploadService->uploadMultiple(
                $request->file('files'),
                $request->input('model_type'),
                $request->input('model_id'),
                $request->input('collection', 'default'),
                $request->input('pathStrategy'),
                $request->input('customPath'),
                $request->input('pathPrefix') ? trim($request->input('pathPrefix')) : null
            );

            return response()->json([
                'success' => true,
                'media' => $uploadedMedia,
                'count' => count($uploadedMedia),
            ]);

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
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete media by ID
     *
     * Authorization: Checks if user can update the model that owns this media
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $media = MediaModel::findOrFail($id);

            // Check authorization if media is attached to a model
            if ($media->model_type && $media->model_id) {
                $ownerModel = $media->model_type::find($media->model_id);
                if ($ownerModel && !\Gate::allows('update', $ownerModel)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to delete this media',
                    ], 403);
                }
            }

            $this->managementService->deleteMedia($id);

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
     *
     * Authorization: Checks if user can update the models that own these media
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

            // Check authorization for each media item
            $mediaItems = MediaModel::whereIn('id', $ids)->get();
            $unauthorizedCount = 0;

            foreach ($mediaItems as $media) {
                if ($media->model_type && $media->model_id) {
                    $ownerModel = $media->model_type::find($media->model_id);
                    if ($ownerModel && !\Gate::allows('update', $ownerModel)) {
                        $unauthorizedCount++;
                    }
                }
            }

            if ($unauthorizedCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Unauthorized to delete {$unauthorizedCount} of {$mediaItems->count()} media item(s)",
                ], 403);
            }

            $deleted = $this->managementService->bulkDelete($ids);

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
     * Delete entire media collection
     */
    public function destroyCollection(Request $request): JsonResponse
    {
        $data = $request->validate([
            'collection' => 'required|string',
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
        ]);

        try {
            $deleted = $this->managementService->destroyCollection(
                $data['model_type'],
                $data['model_id'],
                $data['collection']
            );

            return response()->json([
                'success' => true,
                'deleted' => $deleted,
            ]);
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
            $updated = $this->managementService->reorder($mediaItems);

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
            $props = $request->json()->all();
            unset($props['_token'], $props['_method']);

            $media = $this->managementService->updateProperties($id, $props);

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
     */
    public function cropImage(Request $request, int $id): JsonResponse
    {
        try {
            $media = MediaModel::findOrFail($id);

            if (!in_array($media->mime_type, ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only image files can be cropped',
                ], 400);
            }

            $data = $request->validate([
                'x' => 'required|integer|min:0',
                'y' => 'required|integer|min:0',
                'width' => 'required|integer|min:1',
                'height' => 'required|integer|min:1',
                'maxSize' => 'nullable|integer|min:1',
                'aspectRatio' => 'nullable|string',
            ]);

            $result = $this->imageService->cropImage(
                $media,
                (int)$data['x'],
                (int)$data['y'],
                (int)$data['width'],
                (int)$data['height'],
                $data['maxSize'] ?? null,
                $data['aspectRatio'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Image cropped successfully',
                'media' => $result,
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
            \Log::error('[MediaController] Crop error', [
                'media_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Crop failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @deprecated Use cropImage(). Kept for backward compatibility with older routes.
     */
    public function crop(Request $request, int $id): JsonResponse
    {
        return $this->cropImage($request, $id);
    }

    /**
     * Upload simple file (for File field)
     */
    public function uploadFile(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|max:102400',
                'field' => 'nullable|string',
                'model_type' => 'nullable|string',
                'model_id' => 'nullable',
                'pathStrategy' => 'nullable|in:flat,dated',
                'filenameStrategy' => 'nullable|in:original,transliterate,unique',
                'locale' => 'nullable|string',
                'customPath' => 'nullable|string|max:255',
                'pathPrefix' => 'nullable|string|max:255',
            ]);

            $path = $this->uploadService->uploadFile(
                $request->file('file'),
                $request->input('filenameStrategy'),
                $request->input('pathStrategy'),
                $request->input('customPath'),
                $request->input('model_type'),
                $request->input('model_id'),
                $request->input('locale'),
                $request->input('pathPrefix') ? trim($request->input('pathPrefix')) : null
            );

            return response()->json([
                'success' => true,
                'path' => $path,
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
