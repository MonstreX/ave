<?php

namespace Monstrex\Ave\Media\Services;

class URLGeneratorService
{
    protected ?FileService $fileService;

    public function __construct()
    {
        $this->fileService = app(FileService::class);
    }

    public function handle(array $params): array
    {

        $targetPath = config('ave.media.storage.root', 'media').'/'.
                        ($params['model'] ? $prefix = $params['model']->getTable().'/' : '').
                        date('Y').'/'.date('m').
                        (! empty($params['collectionName']) ? '/'.$params['collectionName'] : '');

        foreach ($params['files'] as $key => $file) {

            // Get Original File Info
            $fileName = $file['sourceFile']->getClientOriginalName();
            $fileName = empty($params['transLang']) ? $fileName : strtr($fileName, config('ave.media.transliterations.'.$params['transLang']));
            $fileInfo = pathinfo($fileName);

            if (! $params['replaceFile']) {
                $fileCopy = 1;
                while ($this->fileService->exists($targetPath.'/'.$fileName)) {
                    $fileCopy++;
                    $fileName = $fileInfo['filename'].'-'.$fileCopy.'.'.$fileInfo['extension'];
                }
            }

            $params['files'][$key]['targetDisk'] = $params['disk'];
            $params['files'][$key]['targetPath'] = $targetPath;
            $params['files'][$key]['targetFullPath'] = $targetPath.'/'.$fileName;
            $params['files'][$key]['targetFileName'] = $fileName;
        }

        return $params['files'];
    }
}
