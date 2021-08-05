<?php

namespace Tests\Unit;

use App\Importance;
use App\Importer;
use App\Milestone;
use App\Type;
use App\Status;
use App\Project;
use App\Ticket;
use App\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;

class ImporterTest extends TestCase
{
    use DatabaseMigrations;
    use DatabaseTransactions;

    public function testImport()
    {
        $milestone = Milestone::create(['name' => 'Test milestone']);
        $user = new User(['email' => 'admin@test.com', 'password' => '123', 'name' => 'Admin']);
        $user->saveOrFail();
        Auth::login($user);

        $this->create(Type::class, ['Regression']);
        $this->create(Importance::class, ['Blocker', 'Critical', 'Major']);
        $this->create(Status::class, ['New', 'Active']);
        $this->create(Project::class, ['Platform'], ['active' => 1]);
        $this->create(User::class, ['Unassigned'], ['email' => 'test@test.com', 'password' => '123']);

        (new Importer())->call(
            $milestone->id,
            __DIR__ . '/../Fixtures/import.csv',
            true,
        );

        $this->assertCount(6, Ticket::all());
    }

    private function create($class, $names, $additionalProps = [])
    {
        foreach ($names as $name) {
            $model = new $class;
            $model->name = $name;
            foreach($additionalProps as $prop => $value) {
                $model->$prop = $value;
            }
            $model->saveOrFail();
        }
    }
}
