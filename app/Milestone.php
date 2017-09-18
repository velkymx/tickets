<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{

  public $timestamps = false;

  protected $fillable = [
      'name','description','start_at','due_at','end_at',
  ];

  public function tickets()
  {
      return $this->hasMany('App\Ticket');
  }
}
