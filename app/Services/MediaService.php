<?php

namespace App\Services;

use App\Models\MediaFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Handles every upload made from the admin dashboard. Files are written to
 * public/uploads/{folder} so they are served directly by the web server.
 */
class MediaService
{
    public const MAX_WIDTH = 2000;

    /**
     * Store an upload and return its web-relative path (e.g. uploads/events/file.jpg).
     */
    public function store(UploadedFile $file, string $folder = 'general', bool $track = true): string
    {
        $folder = trim(Str::slug($folder, '-')) ?: 'general';
        $directory = public_path('uploads/'.$folder);

        File::ensureDirectoryExists($directory, 0755);

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        $name = Str::limit($base, 60, '').'-'.Str::lower(Str::random(6)).'.'.$extension;

        $file->move($directory, $name);

        $path = 'uploads/'.$folder.'/'.$name;
        $absolute = $directory.'/'.$name;

        $this->shrinkOversizedImage($absolute);

        if ($track) {
            MediaFile::create([
                'name' => $file->getClientOriginalName() ?: $name,
                'path' => $path,
                'folder' => $folder,
                'mime_type' => File::mimeType($absolute) ?: null,
                'size' => File::size($absolute) ?: 0,
                'uploaded_by' => Auth::id(),
            ]);
        }

        return $path;
    }

    /**
     * Remove a previously uploaded file (ignores external URLs and missing files).
     */
    public function delete(?string $path): void
    {
        if (blank($path) || Str::startsWith($path, ['http://', 'https://', '//'])) {
            return;
        }

        $relative = ltrim($path, '/');

        if (! Str::startsWith($relative, 'uploads/')) {
            return;
        }

        $absolute = public_path($relative);

        if (File::exists($absolute) && File::isFile($absolute)) {
            File::delete($absolute);
        }

        MediaFile::where('path', $relative)->delete();
    }

    /**
     * Scale very large photos down so pages stay fast on shared hosting.
     */
    protected function shrinkOversizedImage(string $absolute): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            return;
        }

        try {
            $info = @getimagesize($absolute);

            if (! $info || $info[0] <= self::MAX_WIDTH) {
                return;
            }

            [$width, $height, $type] = $info;

            $source = match ($type) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($absolute),
                IMAGETYPE_PNG => @imagecreatefrompng($absolute),
                IMAGETYPE_WEBP => @imagecreatefromwebp($absolute),
                default => null,
            };

            if (! $source) {
                return;
            }

            $newWidth = self::MAX_WIDTH;
            $newHeight = (int) round($height * ($newWidth / $width));
            $canvas = imagecreatetruecolor($newWidth, $newHeight);

            if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
            }

            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            match ($type) {
                IMAGETYPE_JPEG => imagejpeg($canvas, $absolute, 85),
                IMAGETYPE_PNG => imagepng($canvas, $absolute, 8),
                IMAGETYPE_WEBP => imagewebp($canvas, $absolute, 85),
                default => null,
            };

            imagedestroy($canvas);
            imagedestroy($source);
        } catch (\Throwable) {
            // Keep the original file if anything about the resize fails.
        }
    }
}
