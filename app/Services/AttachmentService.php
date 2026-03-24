<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    public function store(UploadedFile $file, string $directory): array
    {
        $path = $file->store($directory, 'public');

        return [
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
    }

    public function delete(string $path): void
    {
        Storage::disk('public')->delete($path);
    }
}
