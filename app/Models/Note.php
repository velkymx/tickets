<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
  protected $fillable = [
      'body', 'created_at', 'user_id', 'ticket_id','hours','notetype'

  ];

  public function user()
  {
      return $this->belongsTo('App\Models\User');
  }

  public function ticket()
  {
      return $this->belongsTo('App\Models\Ticket');
  }

}
