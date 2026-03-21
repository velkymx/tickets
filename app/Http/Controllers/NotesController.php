<?php

namespace App\Http\Controllers;

use Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\Note;

class NotesController extends Controller
{

    public function hide($id){

      $note = Note::findOrFail($id);

      $note->hide = 1;

      $note->update();

      return "Note Removed!";

    }
}
