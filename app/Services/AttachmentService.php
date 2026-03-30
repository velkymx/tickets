<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AttachmentService
{
    private const BLOCKED_MIMES = [
        'application/x-php',
        'application/x-httpd-php',
        'application/x-sh',
        'application/x-shellscript',
        'application/x-csh',
        'application/x-executable',
        'application/x-msdos-program',
        'application/x-msdownload',
        'application/bat',
        'application/cmd',
        'image/svg+xml',
    ];

    private const BLOCKED_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'phps',
        'sh', 'bash', 'csh', 'bat', 'cmd', 'com', 'exe',
        'svg', 'svgz', 'jsp', 'asp', 'aspx',
    ];

    public function store(UploadedFile $file, string $directory): array
    {
        $this->validateFileType($file);

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

    private function validateFileType(UploadedFile $file): void
    {
        $mime = strtolower($file->getMimeType());
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($mime, self::BLOCKED_MIMES) || in_array($extension, self::BLOCKED_EXTENSIONS)) {
            throw ValidationException::withMessages([
                'file' => "File type not allowed: .{$extension} ({$mime})",
            ]);
        }
    }
}
