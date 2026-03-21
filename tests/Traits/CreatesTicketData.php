<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;

trait CreatesTicketData
{
    protected function setUpTicketData(): void
    {
        $statuses = [
            ['id' => 1, 'name' => 'New', 'slug' => 'new'],
            ['id' => 2, 'name' => 'In Progress', 'slug' => 'in-progress'],
            ['id' => 3, 'name' => 'Blocked', 'slug' => 'blocked'],
            ['id' => 4, 'name' => 'Review', 'slug' => 'review'],
            ['id' => 5, 'name' => 'Closed', 'slug' => 'closed'],
            ['id' => 6, 'name' => 'Open', 'slug' => 'open'],
            ['id' => 7, 'name' => 'Resolved', 'slug' => 'resolved'],
            ['id' => 8, 'name' => 'Done', 'slug' => 'done'],
            ['id' => 9, 'name' => 'Cancelled', 'slug' => 'cancelled'],
        ];

        foreach ($statuses as $status) {
            DB::table('statuses')->insert($status);
        }

        $types = [
            ['id' => 1, 'name' => 'Bug', 'icon' => 'fas fa-bug'],
            ['id' => 2, 'name' => 'Feature', 'icon' => 'fas fa-star'],
            ['id' => 3, 'name' => 'Task', 'icon' => 'fas fa-check'],
            ['id' => 4, 'name' => 'Improvement', 'icon' => 'fas fa-arrow-up'],
            ['id' => 5, 'name' => 'Story', 'icon' => 'fas fa-book'],
        ];

        foreach ($types as $type) {
            DB::table('types')->insert($type);
        }

        $importances = [
            ['id' => 1, 'name' => 'Critical', 'icon' => 'fas fa-fire text-danger', 'class' => 'danger'],
            ['id' => 2, 'name' => 'High', 'icon' => 'fas fa-exclamation text-warning', 'class' => 'warning'],
            ['id' => 3, 'name' => 'Medium', 'icon' => 'fas fa-minus text-info', 'class' => 'info'],
            ['id' => 4, 'name' => 'Low', 'icon' => 'fas fa-arrow-down text-secondary', 'class' => 'secondary'],
        ];

        foreach ($importances as $importance) {
            DB::table('importances')->insert($importance);
        }
    }
}
