<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
  protected $fillable = [
      'body', 'created_at', 'user_id', 'ticket_id','hours','notetype'

  ];

  public function user()
  {
      return $this->belongsTo('App\User');
  }

  public function ticket()
  {
      return $this->belongsTo('App\Ticket');
  }

}
