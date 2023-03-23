<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Milestone extends Model
{

  public $timestamps = false;

  protected $fillable = [
      'name','description','scrummaster_user_id','owner_user_id','start_at','due_at','end_at',
  ];

  public function tickets()
  {
      return $this->hasMany('App\Models\Ticket');
  }

  public function owner()
  {
      return $this->hasOne('App\Models\User','id','owner_user_id');
  }
  
  public function scrummaster()
  {
      return $this->hasOne('App\Models\User','id','scrummaster_user_id');
  }  
}
