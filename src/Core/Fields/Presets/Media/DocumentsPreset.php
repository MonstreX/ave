<?php

namespace Monstrex\Ave\Core\Fields\Presets\Media;

/**
 * Documents preset - multiple document and media files
 *
 * Includes: PDFs, Word docs, Excel, archives, videos, audio files
 */
class DocumentsPreset extends MediaPreset
{
    /**
     * Configure for mixed document uploads
     */
    public function apply($field)
    {
        return $field
            ->accept([
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
                'video/mp4',
                'audio/mpeg',
            ])
            ->multiple(true)
            ->maxFileSize(10240);
    }

    public function description(): string
    {
        return 'Documents, archives, videos and audio files (max 10MB)';
    }
}
