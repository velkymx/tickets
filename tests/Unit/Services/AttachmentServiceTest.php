<?php

namespace Tests\Unit\Services;

use App\Services\AttachmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
