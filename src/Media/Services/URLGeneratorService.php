<?php

namespace Monstrex\Ave\Media\Services;

use Monstrex\Ave\Services\FilenameGeneratorService;
use Monstrex\Ave\Services\PathGeneratorService;

class URLGeneratorService
{
    protected ?FileService $fileService;

    public function __construct()
    {
        $this->fileService = app(FileService::class);
    }

    public function handle(array $params): array
    {
        // Get path generation strategy from params or config
        $pathStrategy = $params['pathStrategy'] ?? config('ave.media.path.strategy', 'dated');

        // Get filename generation options from params or config
        $filenameStrategy = $params['filenameStrategy'] ?? config('ave.media.filename.strategy', 'transliterate');
        $filenameSeparator = $params['filenameSeparator'] ?? config('ave.media.filename.separator', '-');
        $filenameLocale = $params['filenameLocale'] ?? config('ave.media.filename.locale', 'ru');
        $replaceFile = $params['replaceFile'] ?? false;

        // Generate target path using PathGeneratorService
        $targetPath = PathGeneratorService::generate([
            'root' => config('ave.media.storage.root', 'media'),
            'strategy' => $pathStrategy,
            'model' => $params['model'] ?? null,
            'recordId' => $params['model'] ? $params['model']->getKey() : null,
            'year' => date('Y'),
            'month' => date('m'),
        ]);

        foreach ($params['files'] as $key => $file) {
            $sourceFile = $file['sourceFile'];

            // Generate filename using FilenameGeneratorService
            $fileName = FilenameGeneratorService::generate(
                $sourceFile->getClientOriginalName(),
                [
                    'strategy' => $filenameStrategy,
                    'separator' => $filenameSeparator,
                    'locale' => $filenameLocale,
                    'uniqueness' => $replaceFile ? FilenameGeneratorService::UNIQUENESS_REPLACE : FilenameGeneratorService::UNIQUENESS_SUFFIX,
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
