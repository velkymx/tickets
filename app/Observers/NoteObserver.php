<?php

namespace App\Observers;

use App\Models\Note;
use App\Services\TicketPulseService;

class NoteObserver
{
    public function created(Note $note): void
    {
        $this->invalidatePulse($note);
    }

    public function updated(Note $note): void
    {
        $this->invalidatePulse($note);
    }

    public function deleted(Note $note): void
    {
        $this->invalidatePulse($note);
    }

    private function invalidatePulse(Note $note): void
    {
        app(TicketPulseService::class)->invalidatePulse($note->ticket_id);
    }
}
