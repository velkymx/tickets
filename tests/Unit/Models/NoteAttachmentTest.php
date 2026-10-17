<?php

namespace Tests\Unit\Models;

use App\Models\NoteAttachment;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class NoteAttachmentTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_exposes_a_url_and_image_flag_accessor(): void
    {
        $attachment = new NoteAttachment([
            'path' => 'attachments/diagram.png',
            'mime_type' => 'image/png',
        ]);

        $this->assertStringEndsWith('/attachments/diagram.png', $attachment->url);
        $this->assertTrue($attachment->is_image);
        $this->assertFalse((new NoteAttachment(['mime_type' => 'application/pdf']))->is_image);
    }
}
