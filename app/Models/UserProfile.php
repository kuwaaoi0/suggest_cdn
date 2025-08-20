<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model {
  protected $fillable = ['site_id','external_user_id'];
  public function site(){ return $this->belongsTo(Site::class); }
  public function prefs(){ return $this->hasMany(UserKeywordPref::class); }
}
