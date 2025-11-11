<?php

namespace Monstrex\Ave\Media\Services;

use Monstrex\Ave\Support\StorageProfile;

class URLGeneratorService
{
    protected ?FileService $fileService;

    public function __construct()
    {
        $this->fileService = app(FileService::class);
    }

    public function handle(array $params): array
    {
        $profileOverrides = $params['storage'] ?? [];
        $profile = StorageProfile::make($profileOverrides);

        if (!empty($params['pathPrefix'])) {
            $profile = $profile->with(['path_prefix' => $params['pathPrefix']]);
        }

        // Check if direct path was provided (from pathGenerator callback)
        $targetPath = $params['directPath'] ?? null;

        if (!$targetPath) {
            $targetPath = $profile->buildPath([
                'pathStrategy' => $params['pathStrategy'] ?? null,
                'model' => $params['model'] ?? null,
                'recordId' => $params['model'] ? $params['model']->getKey() : null,
                'pathPrefix' => $params['pathPrefix'] ?? null,
            ]);
        } else {
            $targetPath = trim($targetPath, '/');
        }

        foreach ($params['files'] as $key => $file) {
            $sourceFile = $file['sourceFile'];

            $fileName = $profile->generateFilename(
                $sourceFile->getClientOriginalName(),
                [
                    'filenameStrategy' => $params['filenameStrategy'] ?? null,
                    'filenameSeparator' => $params['filenameSeparator'] ?? null,
                    'filenameLocale' => $params['filenameLocale'] ?? null,
                    'replaceFile' => $params['replaceFile'] ?? false,
                    'existsCallback' => fn(string $filename) => $this->fileService->exists($targetPath . '/' . $filename),
                ]
            );

            $params['files'][$key]['targetDisk'] = $params['disk'];
            $params['files'][$key]['targetPath'] = $targetPath;
            $params['files'][$key]['targetFullPath'] = $targetPath.'/'.$fileName;
            $params['files'][$key]['targetFileName'] = $fileName;
        }

        return $params['files'];
    }
}
