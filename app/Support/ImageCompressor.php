<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

final class ImageCompressor
{
    private const MAX_DIMENSION = 1280;

    private const INITIAL_QUALITY = 75;

    private const MIN_QUALITY = 40;

    private const TARGET_BYTES = 500 * 1024;

    public static function compressToWebp(UploadedFile $file, string $disk, string $directory, ?string $filename = null): string
    {
        $manager = new ImageManager(new GdDriver);

        $filename ??= uniqid('img_', true).'.webp';
        $relativePath = trim($directory, '/').'/'.$filename;

        $image = $manager->read($file->getRealPath())->orientate()->scaleDown(width: self::MAX_DIMENSION);

        $quality = self::INITIAL_QUALITY;
        $encoded = $image->encode(new WebpEncoder(quality: $quality));

        while (strlen($encoded) > self::TARGET_BYTES && $quality > self::MIN_QUALITY) {
            $quality -= 10;
            $encoded = $image->encode(new WebpEncoder(quality: $quality));
        }

        Storage::disk($disk)->put($relativePath, (string) $encoded, 'public');

        return $relativePath;
    }
}
