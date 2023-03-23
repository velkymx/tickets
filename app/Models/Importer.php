<?php
namespace App\Models;

use App\Exceptions\ImportException;
use App\Type;
use Illuminate\Support\Facades\Auth;

class Importer {

    private $models = [];

    public function call(int $milestoneId, string $filePath, bool $hasHeader)
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
    }

    private function importRow(array $row)
    {
        $ticket = new Ticket();
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
    
    private function relation(string $class, string $value)
    {
        $key = $class . '|' . $value;
        if (isset($this->models[$key])) {
            return $this->models[$key]->id;
        }

        $model = $class::where('name', $value)->first();

        if (!$model) {
            throw new ImportException(class_basename($class) . " $value does not exist.");
        }
        
        $this->models[$key] = $model;

        return $model ? $model->id : null;
    }
}