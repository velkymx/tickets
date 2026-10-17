<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\UsersController;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsersTimeHelperTest extends TestCase
{
    #[Test]
    public function helper_formats_both_forms_from_one_site(): void
    {
        $this->travelTo(Carbon::parse('2026-01-15 14:05', 'America/New_York'));

        $method = new \ReflectionMethod(UsersController::class, 'currentTimeFor');
        $method->setAccessible(true);

        $this->assertSame('14:05 (2:05 pm)', $method->invoke(new UsersController, 'America/New_York'));
    }

    #[Test]
    public function helper_renders_morning_hours(): void
    {
        $this->travelTo(Carbon::parse('2026-01-15 09:30', 'America/New_York'));

        $method = new \ReflectionMethod(UsersController::class, 'currentTimeFor');
        $method->setAccessible(true);

        $this->assertSame('09:30 (9:30 am)', $method->invoke(new UsersController, 'America/New_York'));
    }
}
