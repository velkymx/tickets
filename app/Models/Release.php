<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Release extends Model
{
    public function tickets()
      {
          return $this->hasMany('App\Models\ReleaseTicket', 'release_id');
      }    

      public function owner()
      {
          return $this->hasOne('App\Models\User');
      }      
}
