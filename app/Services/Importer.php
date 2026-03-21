<?php

namespace App\Services;

use App\Models\Importance;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\Type;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;

class Importer
{
    private array $models = [];

    private ?Milestone $milestone = null;

    public function call(int $milestoneId, string $filePath, bool $hasHeader): void
    {
        $this->milestone = Milestone::findOrFail($milestoneId);
        $file = fopen($filePath, 'r');
        $i = 0;
        while ($row = fgetcsv($file)) {
            if ($i === 0 && $hasHeader) {
                $i++;

                continue;
            }
            $this->importRow($row);
            $i++;
        }
        fclose($file);
    }

    private function importRow(array $row): void
    {
        $ticket = new Ticket;
        $ticket->milestone_id = $this->milestone->id;
        $ticket->type_id = $this->relation(Type::class, $row[0]);
        $ticket->subject = $row[1];
        $ticket->description = $row[2];
        $ticket->importance_id = $this->relation(Importance::class, $row[3]);
        $ticket->status_id = $this->relation(Status::class, $row[4]);
        $ticket->project_id = $this->relation(Project::class, $row[5]);
        $ticket->user_id2 = $this->relation(User::class, $row[6]);
        $ticket->user_id = Auth::id();

        $ticket->saveOrFail();
    }

    private function relation(string $class, string $value): ?int
    {
        $key = $class.'|'.$value;
        if (isset($this->models[$key])) {
            return $this->models[$key]->id;
        }

        $model = $class::where('name', $value)->first();

        if (! $model) {
            throw new Exception(class_basename($class)." $value does not exist.");
        }

        $this->models[$key] = $model;

        return $model->id;
    }
}
