<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{

  public $timestamps = false;

  protected $fillable = [
      'name','description','scrummaster_user_id','owner_user_id','start_at','due_at','end_at',
  ];

  public function tickets()
  {
      return $this->hasMany('App\Ticket');
  }

  public function owner()
  {
      return $this->hasOne('App\User','id','owner_user_id');
  }
  
  public function scrummaster()
  {
      return $this->hasOne('App\User','id','scrummaster_user_id');
  }  
}
