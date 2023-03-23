<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{

  public $timestamps = false;

  protected $fillable = [
      'name', 'description', 'active',
  ];

  public function tickets()
  {
      return $this->hasMany('App\Models\Ticket');
  }
}
