<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{

  public $timestamps = false;

  protected $fillable = [
      'name', 'description', 'active',
  ];

  public function tickets()
  {
      return $this->hasMany('App\Ticket');
  }
}
