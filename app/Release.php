<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Release extends Model
{
    public function tickets()
      {
          return $this->hasMany('App\ReleaseTicket', 'release_id');
      }    

      public function owner()
      {
          return $this->hasOne('App\User');
      }      
}
