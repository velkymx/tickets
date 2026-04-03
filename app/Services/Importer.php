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
use Illuminate\Support\Facades\DB;

class Importer
{
    private array $models = [];

    private ?Milestone $milestone = null;

    private ?int $userId = null;

    public function call(int $milestoneId, string $filePath, bool $hasHeader, ?int $userId = null): void
    {
        $this->userId = $userId;
        $this->milestone = Milestone::findOrFail($milestoneId);

        $file = fopen($filePath, 'r');
        if ($file === false) {
            throw new Exception("Unable to open file: {$filePath}");
        }

        try {
            $i = 0;
            $chunk = [];

            while ($row = fgetcsv($file)) {
                if ($i === 0 && $hasHeader) {
                    $i++;

                    continue;
                }

                if (count($row) < 7) {
                    throw new Exception('CSV row must have at least 7 columns. Row '.($i + 1).' has '.count($row).' columns.');
                }

                $chunk[] = ['row' => $row, 'index' => $i + 1];
                $i++;

                if (count($chunk) >= 100) {
                    $this->importChunk($chunk);
                    $chunk = [];
                }
            }

            if (count($chunk) > 0) {
                $this->importChunk($chunk);
            }
        } finally {
            fclose($file);
        }
    }

    private function importChunk(array $chunk): void
    {
        DB::transaction(function () use ($chunk) {
            foreach ($chunk as $item) {
                $this->importRow($item['row'], $item['index']);
            }
        });
    }

    private function importRow(array $row, int $rowIndex): void
    {
        if (empty(trim($row[1] ?? ''))) {
            throw new Exception("Row $rowIndex: Subject (column 2) is required.");
        }

        $ticket = new Ticket;
        $ticket->milestone_id = $this->milestone->id;
        $ticket->type_id = $this->relation(Type::class, $row[0] ?? '', $rowIndex, 1);
        $ticket->subject = trim($row[1]);
        $ticket->description = $row[2] ?? '';
        $ticket->importance_id = $this->relation(Importance::class, $row[3] ?? '', $rowIndex, 4);
        $ticket->status_id = $this->relation(Status::class, $row[4] ?? '', $rowIndex, 5);
        $ticket->project_id = $this->relation(Project::class, $row[5] ?? '', $rowIndex, 6);
        $ticket->user_id2 = $this->relation(User::class, $row[6] ?? '', $rowIndex, 7);
        $ticket->user_id = $this->userId ?? Auth::id();

        $ticket->saveOrFail();
    }

    private function relation(string $class, string $value, int $rowIndex, int $columnNumber): ?int
    {
        if (empty(trim($value))) {
            throw new Exception("Row $rowIndex: Column ".$columnNumber.' ('.class_basename($class).') is required.');
        }

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
