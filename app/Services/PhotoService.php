<?php

namespace App\Services;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use SergiX44\Nutgram\Nutgram;
use Throwable;

class PhotoService
{
    private const MAX_SIDE = 1600;
    private const THUMB_SIDE = 400;
    private const QUALITY = 82;
    private const DISK = 'local';

    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = ImageManager::usingDriver(Driver::class);
    }

    public function storePath(string $path, Model $owner, string $source, ?User $user = null): Photo
    {
        return $this->save($this->manager->decodePath($path), $owner, $source, $user);
    }

    public function storeUploaded(UploadedFile $file, Model $owner, string $source, ?User $user = null): Photo
    {
        return $this->storePath($file->getRealPath(), $owner, $source, $user);
    }

    public function storeFromTelegram(Nutgram $bot, array $fileIds, Model $owner): void
    {
        foreach ($fileIds as $fileId) {
            $tmp = Storage::disk(self::DISK)->path('tmp/' . Str::uuid()->toString() . '.jpg');

            try {
                $file = $bot->getFile($fileId);

                if ($file === null || ! $bot->downloadFile($file, $tmp)) {
                    continue;
                }

                $this->storePath($tmp, $owner, Photo::SOURCE_TELEGRAM);
            } catch (Throwable $e) {
                report($e);
            } finally {
                if (is_file($tmp)) {
                    unlink($tmp);
                }
            }
        }
    }

    private function save(ImageInterface $image, Model $owner, string $source, ?User $user): Photo
    {
        $image->orient();

        $name = Str::uuid()->toString();
        $dir = 'photos/'.now()->format('Y/m');
        $path = $dir.'/'.$name.'.jpg';
        $thumbPath = $dir.'/'.$name.'_thumb.jpg';

        $image->scaleDown(width: self::MAX_SIDE, height: self::MAX_SIDE);

        $width = $image->width();
        $height = $image->height();

        $encoded = (string) $image->encodeUsingFileExtension('jpg', quality: self::QUALITY);
        Storage::disk(self::DISK)->put($path, $encoded);

        $thumb = (string) $image
            ->scaleDown(width: self::THUMB_SIDE, height: self::THUMB_SIDE)
            ->encodeUsingFileExtension('jpg', quality: self::QUALITY);
        Storage::disk(self::DISK)->put($thumbPath, $thumb);

        $photo = new Photo([
            'path' => $path,
            'thumb_path' => $thumbPath,
            'width' => $width,
            'height' => $height,
            'size' => strlen($encoded),
            'source' => $source,
        ]);

        $photo->photoable()->associate($owner);
        $photo->uploaded_by = $user?->id;
        $photo->save();

        return $photo;
    }
}
