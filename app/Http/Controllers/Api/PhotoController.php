<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoController extends Controller
{
    private const DISK = 'local';

    // GET /api/photos/{id} — полный размер
    public function show(Photo $photo): StreamedResponse
    {
        return $this->stream($photo->path);
    }

    // GET /api/photos/{id}/thumb — миниатюра
    public function thumb(Photo $photo): StreamedResponse
    {
        return $this->stream($photo->thumb_path);
    }

    private function stream(string $path): StreamedResponse
    {
        abort_unless(Storage::disk(self::DISK)->exists($path), 404);

        return Storage::disk(self::DISK)->response($path, null, [
            'Cache-Control' => 'private, max-age=31536000, immutable',
        ]);
    }
}
