<?php

namespace Monstrex\Ave\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Monstrex\Ave\Services\FileManagerService;

class FileManagerController extends Controller
{
    public function __construct(
        private FileManagerService $fileManager
    ) {
    }

    /**
     * Display file manager page.
     */
    public function index(Request $request): View
    {
        $path = $request->get('path') ?? '';
        $data = $this->fileManager->list($path);

        return view('ave::file-manager.index', [
            'data' => $data,
            'currentPath' => $path,
        ]);
    }

    /**
     * List files in directory (AJAX).
     */
    public function list(Request $request): JsonResponse
    {
        $path = $request->get('path') ?? '';
        $data = $this->fileManager->list($path);

        return response()->json($data);
    }

    /**
     * Read file content.
     */
    public function read(Request $request): JsonResponse
    {
        $path = $request->get('path') ?? '';
        $data = $this->fileManager->read($path);

        if (isset($data['error'])) {
            return response()->json($data, 404);
        }

        return response()->json($data);
    }

    /**
     * Save file content.
     */
    public function save(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'content' => 'present|string',
        ]);

        $result = $this->fileManager->save(
            $request->input('path'),
            $request->input('content')
        );

        if (isset($result['error'])) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Create new directory.
     */
    public function createDirectory(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'nullable|string',
            'name' => 'required|string|max:255',
        ]);

        $result = $this->fileManager->createDirectory(
            $request->input('path', ''),
            $request->input('name')
        );

        if (isset($result['error'])) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Upload file.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'nullable|string',
            'file' => 'required|file',
        ]);

        $result = $this->fileManager->upload(
            $request->input('path', ''),
            $request->file('file')
        );

        if (isset($result['error'])) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Delete file or directory.
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $result = $this->fileManager->delete($request->input('path'));

        if (isset($result['error'])) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    /**
     * Rename file or directory.
     */
    public function rename(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
            'name' => 'required|string|max:255',
        ]);

        $result = $this->fileManager->rename(
            $request->input('path'),
            $request->input('name')
        );

        if (isset($result['error'])) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }
}
