<?php

namespace Monstrex\Ave\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;

class FileManagerService
{
    protected string $rootPath;
    protected array $editableExtensions;
    protected array $forbiddenExtensions;
    protected int $maxUploadSize;

    public function __construct()
    {
        $this->rootPath = base_path(config('ave.file_manager.root_path', 'public'));
        $this->editableExtensions = config('ave.file_manager.editable_extensions', []);
        $this->forbiddenExtensions = config('ave.file_manager.forbidden_extensions', []);
        $this->maxUploadSize = config('ave.file_manager.max_upload_size', 10240);
    }

    /**
     * Get list of files and directories.
     */
    public function list(string $path = ''): array
    {
        $fullPath = $this->getFullPath($path);

        if (! File::isDirectory($fullPath)) {
            return ['error' => 'Directory not found'];
        }

        $items = [];
        $directories = File::directories($fullPath);
        $files = File::files($fullPath);

        // Add directories first
        foreach ($directories as $dir) {
            $name = basename($dir);
            $items[] = [
                'name' => $name,
                'path' => $this->getRelativePath($dir),
                'type' => 'directory',
                'size' => null,
                'modified' => File::lastModified($dir),
            ];
        }

        // Then files
        foreach ($files as $file) {
            $name = $file->getFilename();
            $extension = strtolower($file->getExtension());
            $relativePath = $this->getRelativePath($file->getPathname());

            $items[] = [
                'name' => $name,
                'path' => $relativePath,
                'type' => 'file',
                'extension' => $extension,
                'size' => $file->getSize(),
                'modified' => $file->getMTime(),
                'editable' => $this->isEditable($extension),
                'url' => $this->getPublicUrl($relativePath),
            ];
        }

        return [
            'current_path' => $path,
            'parent_path' => $path ? dirname($path) : null,
            'items' => $items,
        ];
    }

    /**
     * Read file content.
     */
    public function read(string $path): array
    {
        $fullPath = $this->getFullPath($path);

        if (! File::exists($fullPath) || File::isDirectory($fullPath)) {
            return ['error' => 'File not found'];
        }

        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);

        if (! $this->isEditable($extension)) {
            return ['error' => 'File type not editable'];
        }

        return [
            'path' => $path,
            'name' => basename($path),
            'content' => File::get($fullPath),
            'extension' => $extension,
        ];
    }

    /**
     * Save file content.
     */
    public function save(string $path, string $content): array
    {
        $fullPath = $this->getFullPath($path);
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);

        if (! $this->isEditable($extension)) {
            return ['error' => 'File type not editable'];
        }

        if ($this->isForbidden($extension)) {
            return ['error' => 'Forbidden file type'];
        }

        // Ensure directory exists
        $directory = dirname($fullPath);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($fullPath, $content);

        return ['success' => true, 'message' => 'File saved successfully'];
    }

    /**
     * Create new directory.
     */
    public function createDirectory(string $path, string $name): array
    {
        $name = $this->sanitizeName($name);
        $fullPath = $this->getFullPath($path) . DIRECTORY_SEPARATOR . $name;

        if (File::exists($fullPath)) {
            return ['error' => 'Directory already exists'];
        }

        File::makeDirectory($fullPath, 0755, true);

        return ['success' => true, 'message' => 'Directory created'];
    }

    /**
     * Upload file.
     */
    public function upload(string $path, UploadedFile $file): array
    {
        $extension = $file->getClientOriginalExtension();

        if ($this->isForbidden($extension)) {
            return ['error' => 'Forbidden file type'];
        }

        if ($file->getSize() > $this->maxUploadSize * 1024) {
            return ['error' => 'File too large'];
        }

        $name = $this->sanitizeName($file->getClientOriginalName());
        $fullPath = $this->getFullPath($path);

        $file->move($fullPath, $name);

        return ['success' => true, 'message' => 'File uploaded', 'name' => $name];
    }

    /**
     * Delete file or directory.
     */
    public function delete(string $path): array
    {
        $fullPath = $this->getFullPath($path);

        if (! File::exists($fullPath)) {
            return ['error' => 'File not found'];
        }

        // Prevent deleting root
        if ($fullPath === $this->rootPath) {
            return ['error' => 'Cannot delete root directory'];
        }

        if (File::isDirectory($fullPath)) {
            File::deleteDirectory($fullPath);
        } else {
            File::delete($fullPath);
        }

        return ['success' => true, 'message' => 'Deleted successfully'];
    }

    /**
     * Rename file or directory.
     */
    public function rename(string $path, string $newName): array
    {
        $fullPath = $this->getFullPath($path);

        if (! File::exists($fullPath)) {
            return ['error' => 'File not found'];
        }

        $newName = $this->sanitizeName($newName);
        $newPath = dirname($fullPath) . DIRECTORY_SEPARATOR . $newName;

        if (File::exists($newPath)) {
            return ['error' => 'Name already exists'];
        }

        // Check forbidden extension for files
        if (! File::isDirectory($fullPath)) {
            $extension = pathinfo($newName, PATHINFO_EXTENSION);
            if ($this->isForbidden($extension)) {
                return ['error' => 'Forbidden file extension'];
            }
        }

        File::move($fullPath, $newPath);

        return ['success' => true, 'message' => 'Renamed successfully'];
    }

    /**
     * Get full filesystem path.
     */
    protected function getFullPath(string $path): string
    {
        $path = $this->sanitizePath($path);
        return $this->rootPath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Get relative path from root.
     */
    protected function getRelativePath(string $fullPath): string
    {
        return str_replace($this->rootPath . DIRECTORY_SEPARATOR, '', $fullPath);
    }

    /**
     * Sanitize path to prevent directory traversal.
     */
    protected function sanitizePath(string $path): string
    {
        // Remove leading/trailing slashes
        $path = trim($path, '/\\');

        // Normalize directory separator
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        // Remove any directory traversal attempts
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $safe = [];

        foreach ($parts as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($safe);
                continue;
            }
            $safe[] = $part;
        }

        return implode(DIRECTORY_SEPARATOR, $safe);
    }

    /**
     * Sanitize file/directory name.
     */
    protected function sanitizeName(string $name): string
    {
        // Remove path separators and dangerous characters
        return preg_replace('/[\/\\\\:*?"<>|]/', '', $name);
    }

    /**
     * Check if file extension is editable.
     */
    protected function isEditable(string $extension): bool
    {
        $extension = strtolower($extension);
        return in_array($extension, $this->editableExtensions, true);
    }

    /**
     * Check if file extension is forbidden.
     */
    protected function isForbidden(string $extension): bool
    {
        $extension = strtolower($extension);
        return in_array($extension, $this->forbiddenExtensions, true);
    }

    /**
     * Get public URL for a file.
     */
    protected function getPublicUrl(string $relativePath): string
    {
        // Convert backslashes to forward slashes for URL
        $urlPath = str_replace('\\', '/', $relativePath);
        return asset($urlPath);
    }
}
