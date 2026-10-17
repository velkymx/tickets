<?php

namespace Tests\Unit\Models;

use App\Models\Project;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\SeedsDatabase;

class ProjectTest extends TestCase
{
    use SeedsDatabase;

    #[Test]
    public function it_has_timestamps_disabled(): void
    {
        $project = new Project;

        $this->assertFalse($project->timestamps);
    }
}
