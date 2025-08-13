<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UserKeywordPref extends Model {
  protected $fillable = ['user_profile_id','keyword_id','visibility','boost'];
  public function user(){ return $this->belongsTo(UserProfile::class,'user_profile_id'); }
  public function keyword(){ return $this->belongsTo(Keyword::class); }
}
