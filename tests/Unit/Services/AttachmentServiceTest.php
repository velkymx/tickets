<?php

namespace Tests\Unit\Services;

use App\Services\AttachmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttachmentServiceTest extends TestCase
{
    #[Test]
    public function it_stores_a_file_and_returns_metadata(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);
        $service = new AttachmentService;

        $result = $service->store($file, 'kb-attachments/1');

        Storage::disk('public')->assertExists($result['path']);
        $this->assertSame('photo.jpg', $result['filename']);
        $this->assertSame('image/jpeg', $result['mime_type']);
        $this->assertGreaterThan(0, $result['size']);
    }

    #[Test]
    public function it_rejects_executable_file_types(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('shell.php', 100, 'application/x-php');
        $service = new AttachmentService;

        $this->expectException(ValidationException::class);
        $service->store($file, 'test');
    }

    #[Test]
    public function it_rejects_svg_files(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('image.svg', 100, 'image/svg+xml');
        $service = new AttachmentService;

        $this->expectException(ValidationException::class);
        $service->store($file, 'test');
    }

    #[Test]
    public function it_allows_safe_file_types(): void
    {
        Storage::fake('public');
        $service = new AttachmentService;

        $pdf = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $result = $service->store($pdf, 'test');
        Storage::disk('public')->assertExists($result['path']);

        $txt = UploadedFile::fake()->create('notes.txt', 100, 'text/plain');
        $result = $service->store($txt, 'test');
        Storage::disk('public')->assertExists($result['path']);
    }

    #[Test]
    public function it_deletes_a_file(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('photo.jpg');
        $service = new AttachmentService;
        $result = $service->store($file, 'test');

        $service->delete($result['path']);

        Storage::disk('public')->assertMissing($result['path']);
    }
}
