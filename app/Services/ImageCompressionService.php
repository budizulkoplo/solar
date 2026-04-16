<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageCompressionService
{
    public function storeUploadedFile(
        UploadedFile $file,
        string $directory,
        string $disk = 'public',
        ?string $filename = null,
        array $options = []
    ): string {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $filename ??= pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '_' . uniqid() . '.' . $extension;
        $relativePath = trim($directory . '/' . $filename, '/');

        if (!$this->isCompressibleImage($extension)) {
            return $file->storeAs($directory, $filename, $disk);
        }

        $sourcePath = $file->getRealPath();
        $tempPath = tempnam(sys_get_temp_dir(), 'imgc_');

        if (!$sourcePath || !$tempPath) {
            return $file->storeAs($directory, $filename, $disk);
        }

        try {
            $compressed = $this->compressImageFile($sourcePath, $tempPath, $extension, $options);

            if (!$compressed) {
                return $file->storeAs($directory, $filename, $disk);
            }

            Storage::disk($disk)->put($relativePath, file_get_contents($tempPath));

            return $relativePath;
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function compressStoredImage(string $relativePath, string $disk = 'public', array $options = []): ?array
    {
        $fullPath = Storage::disk($disk)->path($relativePath);

        if (!is_file($fullPath)) {
            return null;
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (!$this->isCompressibleImage($extension)) {
            return null;
        }

        $before = filesize($fullPath) ?: 0;
        $minimumBytes = ((int) ($options['min_bytes'] ?? 0));

        if ($minimumBytes > 0 && $before < $minimumBytes) {
            return [
                'path' => $relativePath,
                'before' => $before,
                'after' => $before,
                'skipped' => true,
            ];
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'imgc_');
        if (!$tempPath) {
            return null;
        }

        try {
            $compressed = $this->compressImageFile($fullPath, $tempPath, $extension, $options);
            if (!$compressed) {
                return null;
            }

            copy($tempPath, $fullPath);
            clearstatcache(true, $fullPath);
            $after = filesize($fullPath) ?: 0;

            return [
                'path' => $relativePath,
                'before' => $before,
                'after' => $after,
                'saved' => max(0, $before - $after),
                'skipped' => false,
            ];
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private function compressImageFile(string $sourcePath, string $targetPath, string $extension, array $options = []): bool
    {
        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            return false;
        }

        [$width, $height, $imageType] = $imageInfo;
        $source = match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            default => null,
        };

        if (!$source) {
            return false;
        }

        $source = $this->applyOrientation($sourcePath, $source, $imageType);

        $maxWidth = (int) ($options['max_width'] ?? 1600);
        $maxHeight = (int) ($options['max_height'] ?? 1600);
        $quality = (int) ($options['quality'] ?? 75);
        $pngCompression = (int) ($options['png_compression'] ?? 9);

        $scale = min(
            1,
            $maxWidth > 0 ? $maxWidth / max(1, imagesx($source)) : 1,
            $maxHeight > 0 ? $maxHeight / max(1, imagesy($source)) : 1
        );

        $targetWidth = max(1, (int) floor(imagesx($source) * $scale));
        $targetHeight = max(1, (int) floor(imagesy($source) * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if (!$canvas) {
            imagedestroy($source);
            return false;
        }

        if ($imageType === IMAGETYPE_PNG) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
        }

        imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            imagesx($source),
            imagesy($source)
        );

        $saved = match ($imageType) {
            IMAGETYPE_JPEG => imagejpeg($canvas, $targetPath, max(30, min(90, $quality))),
            IMAGETYPE_PNG => imagepng($canvas, $targetPath, max(0, min(9, $pngCompression))),
            default => false,
        };

        imagedestroy($source);
        imagedestroy($canvas);

        return $saved && is_file($targetPath);
    }

    private function applyOrientation(string $sourcePath, \GdImage $image, int $imageType): \GdImage
    {
        if ($imageType !== IMAGETYPE_JPEG || !function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($sourcePath);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        return match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }

    private function isCompressibleImage(string $extension): bool
    {
        return in_array(strtolower($extension), ['jpg', 'jpeg', 'png'], true);
    }
}
