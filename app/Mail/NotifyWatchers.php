<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyWatchers extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;

    /**
     * Create a new message instance.
     *
     * @return void
     */
     public function __construct(\App\Ticket $ticket)
         {
             $this->ticket = $ticket;
         }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
        ->subject('Re: '.$this->ticket->subject.' (#'.$this->ticket->id.')')        
        ->view('mail.notify');
    }
}
