<?php

namespace Monstrex\Ave\Media;

use RuntimeException;

class ImageProcessor
{
    /**
     * GD image resource
     */
    private $imageResource = null;

    /**
     * Original image dimensions
     */
    private int $originalWidth = 0;
    private int $originalHeight = 0;

    /**
     * Current image dimensions
     */
    private int $currentWidth = 0;
    private int $currentHeight = 0;

    /**
     * Image MIME type
     */
    private string $mimeType = '';

    /**
     * Transformation parameters
     */
    private int $targetWidth = 0;
    private int $targetHeight = 0;
    private string $format = '';
    private int $quality = 75;

    /**
     * Read image from file path
     */
    public function read(string $path): self
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Image file not found: {$path}");
        }

        if (!is_readable($path)) {
            throw new RuntimeException("Image file not readable: {$path}");
        }

        $this->mimeType = mime_content_type($path);

        try {
            $this->imageResource = $this->createImageFromFile($path);
        } catch (RuntimeException $e) {
            throw new RuntimeException("Failed to read image: " . $e->getMessage());
        }

        if (!is_resource($this->imageResource) && !($this->imageResource instanceof \GdImage)) {
            throw new RuntimeException("Failed to create GD image resource");
        }

        $this->originalWidth = imagesx($this->imageResource);
        $this->originalHeight = imagesy($this->imageResource);
        $this->currentWidth = $this->originalWidth;
        $this->currentHeight = $this->originalHeight;

        return $this;
    }

    /**
     * Create image resource from file based on MIME type
     */
    private function createImageFromFile(string $path)
    {
        $mimeType = $this->mimeType;

        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                return imagecreatefromjpeg($path);

            case 'image/png':
                $image = imagecreatefrompng($path);
                if ($image) {
                    // Preserve transparency for PNG
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                }
                return $image;

            case 'image/gif':
                return imagecreatefromgif($path);

            case 'image/webp':
                return imagecreatefromwebp($path);

            case 'image/svg+xml':
                // SVG cannot be processed by GD, return error
                throw new RuntimeException("SVG files are not supported for image processing");

            default:
                // Try generic approach
                $content = file_get_contents($path);
                return imagecreatefromstring($content);
        }
    }

    /**
     * Resize image covering target dimensions (crop with center alignment)
     * If both width and height specified, crops to fit
     */
    public function cover(int $width, int $height): self
    {
        if (!$this->imageResource) {
            throw new RuntimeException("No image loaded");
        }

        if ($width <= 0 || $height <= 0) {
            throw new RuntimeException("Width and height must be positive integers");
        }

        $srcWidth = $this->currentWidth;
        $srcHeight = $this->currentHeight;

        // Calculate aspect ratios
        $srcAspect = $srcWidth / $srcHeight;
        $dstAspect = $width / $height;

        if ($srcAspect > $dstAspect) {
            // Source is wider: scale by height then crop width
            $newHeight = $height;
            $newWidth = (int)($height * $srcAspect);
        } else {
            // Source is taller: scale by width then crop height
            $newWidth = $width;
            $newHeight = (int)($width / $srcAspect);
        }

        // Scale to intermediate size
        $scaledImage = imagescale($this->imageResource, $newWidth, $newHeight);
        if (!$scaledImage) {
            throw new RuntimeException("Failed to scale image");
        }

        // Crop from center
        $x = (int)(($newWidth - $width) / 2);
        $y = (int)(($newHeight - $height) / 2);

        $croppedImage = imagecrop($scaledImage, [
            'x' => max(0, $x),
            'y' => max(0, $y),
            'width' => $width,
            'height' => $height,
        ]);

        imagedestroy($scaledImage);

        if (!$croppedImage) {
            throw new RuntimeException("Failed to crop image");
        }

        if ($this->imageResource !== $this->imageResource) {
            imagedestroy($this->imageResource);
        }

        $this->imageResource = $croppedImage;
        $this->currentWidth = $width;
        $this->currentHeight = $height;

        return $this;
    }

    /**
     * Scale image preserving aspect ratio
     * If only width specified, height scales proportionally (and vice versa)
     */
    public function scale(?int $width = null, ?int $height = null): self
    {
        if (!$this->imageResource) {
            throw new RuntimeException("No image loaded");
        }

        if ($width === null && $height === null) {
            return $this;
        }

        if (($width !== null && $width <= 0) || ($height !== null && $height <= 0)) {
            throw new RuntimeException("Width and height must be positive integers if specified");
        }

        $srcWidth = $this->currentWidth;
        $srcHeight = $this->currentHeight;

        if ($width && !$height) {
            // Scale by width, preserve aspect
            $height = (int)($width * $srcHeight / $srcWidth);
        } elseif ($height && !$width) {
            // Scale by height, preserve aspect
            $width = (int)($height * $srcWidth / $srcHeight);
        }

        $scaledImage = imagescale($this->imageResource, $width, $height);
        if (!$scaledImage) {
            throw new RuntimeException("Failed to scale image to {$width}x{$height}");
        }

        imagedestroy($this->imageResource);
        $this->imageResource = $scaledImage;
        $this->currentWidth = $width;
        $this->currentHeight = $height;

        return $this;
    }

    /**
     * Set format for encoding
     */
    public function format(string $format): self
    {
        $this->format = strtolower($format);
        return $this;
    }

    /**
     * Set quality for lossy formats (JPEG, WEBP, AVIF)
     */
    public function quality(int $quality): self
    {
        $this->quality = max(1, min(100, $quality));
        return $this;
    }

    /**
     * Encode and export image to specified format
     *
     * Supported formats: jpeg, png, gif, webp, avif
     * Quality applies to lossy formats (jpeg, webp, avif)
     *
     * Examples:
     *   $processor->read('image.jpg')->scale(200)->encode('webp', 85);
     *   $processor->read('image.png')->cover(100, 100)->encode('jpeg');
     *   $processor->read('photo.jpg')->encode();  // defaults to jpeg
     *
     * @param string|null $format Output format (jpeg, png, gif, webp, avif)
     * @param int|null $quality Quality 1-100 for lossy formats
     * @return string Binary image data
     */
    public function encode(?string $format = null, ?int $quality = null): string
    {
        if (!$this->imageResource) {
            throw new RuntimeException("No image loaded");
        }

        // Resolve format and quality
        $format = strtolower($format ?? $this->format ?? 'jpeg');
        if ($quality !== null) {
            $this->quality = max(1, min(100, $quality));
        }

        // Encode based on format
        return match ($format) {
            'jpg', 'jpeg' => $this->encodeJpeg(),
            'png' => $this->encodePng(),
            'gif' => $this->encodeGif(),
            'webp' => $this->encodeWebp(),
            'avif' => $this->encodeAvif(),
            default => $this->encodeJpeg(),
        };
    }

    /**
     * Internal: Encode as JPEG
     */
    private function encodeJpeg(): string
    {
        ob_start();
        imagejpeg($this->imageResource, null, $this->quality);
        $content = ob_get_clean();

        if ($content === false) {
            throw new RuntimeException("Failed to encode image as JPEG");
        }

        return $content;
    }

    /**
     * Internal: Encode as PNG
     */
    private function encodePng(): string
    {
        ob_start();
        imagepng($this->imageResource);
        $content = ob_get_clean();

        if ($content === false) {
            throw new RuntimeException("Failed to encode image as PNG");
        }

        return $content;
    }

    /**
     * Internal: Encode as GIF
     */
    private function encodeGif(): string
    {
        ob_start();
        imagegif($this->imageResource);
        $content = ob_get_clean();

        if ($content === false) {
            throw new RuntimeException("Failed to encode image as GIF");
        }

        return $content;
    }

    /**
     * Internal: Encode as WEBP
     */
    private function encodeWebp(): string
    {
        if (!function_exists('imagewebp')) {
            throw new RuntimeException("WEBP support is not available in this PHP installation");
        }

        ob_start();
        imagewebp($this->imageResource, null, $this->quality);
        $content = ob_get_clean();

        if ($content === false) {
            throw new RuntimeException("Failed to encode image as WEBP");
        }

        return $content;
    }

    /**
     * Internal: Encode as AVIF (with WEBP fallback)
     */
    private function encodeAvif(): string
    {
        if (!function_exists('imageavif')) {
            // Fallback to WEBP if AVIF not available
            return $this->encodeWebp();
        }

        ob_start();
        imageavif($this->imageResource, null, $this->quality);
        $content = ob_get_clean();

        if ($content === false) {
            throw new RuntimeException("Failed to encode image as AVIF");
        }

        return $content;
    }

    /**
     * Get image dimensions
     */
    public function getDimensions(): array
    {
        return [
            'width' => $this->currentWidth,
            'height' => $this->currentHeight,
        ];
    }

    /**
     * Get original image dimensions
     */
    public function getOriginalDimensions(): array
    {
        return [
            'width' => $this->originalWidth,
            'height' => $this->originalHeight,
        ];
    }

    /**
     * Cleanup resources
     */
    public function __destruct()
    {
        if ($this->imageResource && (is_resource($this->imageResource) || $this->imageResource instanceof \GdImage)) {
            imagedestroy($this->imageResource);
        }
    }
}
