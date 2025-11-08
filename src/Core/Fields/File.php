<?php

namespace Monstrex\Ave\Core\Fields;

/**
 * File Field
 *
 * A file upload field for uploading files (documents, archives, etc).
 *
 * Features:
 * - Single or multiple file upload
 * - Accept specific file types
 * - Maximum file size validation
 * - File count constraints
 * - Drag & drop support via HTML5
 *
 * Example (Single file):
 *   File::make('document')
 *       ->label('Upload Document')
 *       ->accept(['application/pdf', 'application/msword'])
 *       ->maxFileSize(5120) // 5MB in KB
 *
 * Example (Multiple files):
 *   File::make('attachments')
 *       ->label('Upload Files')
 *       ->multiple(true)
 *       ->maxFileSize(10240) // 10MB
 *       ->accept([
 *           'application/pdf',
 *           'application/vnd.ms-excel',
 *           'text/plain'
 *       ])
 */
class File extends AbstractField
{
    /**
     * Allow multiple file uploads
     */
    protected bool $multipleFiles = false;

    /**
     * Accepted MIME types
     */
    protected array $acceptedMimes = [];

    /**
     * Maximum file size in KB
     */
    protected ?int $maxSizeKb = null;

    /**
     * Maximum number of files (for multiple uploads)
     */
    protected ?int $maxFiles = null;

    /**
     * Minimum number of files required
     */
    protected ?int $minFiles = null;

    /**
     * Enable/disable multiple file uploads
     *
     * @param bool $multiple Whether to allow multiple files
     * @return static
     */
    public function multiple(bool $multiple = true): static
    {
        $this->multipleFiles = $multiple;
        return $this;
    }

    /**
     * Check if multiple files are allowed
     *
     * @return bool
     */
    public function isMultiple(): bool
    {
        return $this->multipleFiles;
    }

    /**
     * Set accepted MIME types
     *
     * @param array $mimes Array of MIME types (e.g., 'application/pdf', 'image/jpeg')
     * @return static
     */
    public function accept(array $mimes): static
    {
        $this->acceptedMimes = $mimes;
        return $this;
    }

    /**
     * Get accepted MIME types
     *
     * @return array
     */
    public function getAcceptedMimes(): array
    {
        return $this->acceptedMimes;
    }

    /**
     * Set maximum file size in KB
     *
     * @param int $sizeKb Maximum size in kilobytes
     * @return static
     */
    public function maxFileSize(int $sizeKb): static
    {
        $this->maxSizeKb = $sizeKb;
        return $this;
    }

    /**
     * Get maximum file size in KB
     *
     * @return int|null
     */
    public function getMaxFileSize(): ?int
    {
        return $this->maxSizeKb;
    }

    /**
     * Set maximum number of files
     *
     * @param int $maxFiles Maximum number of files allowed
     * @return static
     */
    public function maxFiles(int $maxFiles): static
    {
        $this->maxFiles = $maxFiles;
        return $this;
    }

    /**
     * Get maximum number of files
     *
     * @return int|null
     */
    public function getMaxFiles(): ?int
    {
        return $this->maxFiles;
    }

    /**
     * Set minimum number of files required
     *
     * @param int $minFiles Minimum number of files required
     * @return static
     */
    public function minFiles(int $minFiles): static
    {
        $this->minFiles = $minFiles;
        return $this;
    }

    /**
     * Get minimum number of files
     *
     * @return int|null
     */
    public function getMinFiles(): ?int
    {
        return $this->minFiles;
    }

    /**
     * Convert field to array representation for Blade template
     *
     * @return array Field data
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'multiple'       => $this->multipleFiles,
            'acceptedMimes'  => $this->acceptedMimes,
            'maxFileSize'    => $this->maxSizeKb,
            'maxFiles'       => $this->maxFiles,
            'minFiles'       => $this->minFiles,
        ]);
    }
}
