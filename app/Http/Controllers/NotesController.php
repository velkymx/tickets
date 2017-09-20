<?php

namespace App\Http\Controllers;

use Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Note;

class NotesController extends Controller
{

  /**
   * Create a new controller instance.
   *
   * @return void
   */
  public function __construct()
  {
      $this->middleware('auth');
  }    

    public function hide($id){

      $note = Note::findOrFail($id);

      $note->hide = 1;

      $note->update();

      return "Note Removed!";

    }
}
