<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserKeyword extends Model
{
    protected $fillable = [
        'user_profile_id','site_id','genre_id',
        'label','reading','label_norm','reading_norm',
        'weight','visibility','boost','is_active'
    ];

    public function user(){ return $this->belongsTo(UserProfile::class, 'user_profile_id'); }
    public function site(){ return $this->belongsTo(Site::class); }
    public function genre(){ return $this->belongsTo(Genre::class); }
    public function aliases(){ return $this->hasMany(UserKeywordAlias::class); }

    protected static function booted()
    {
        static::saving(function(self $k){
            $norm = function($s){
                if($s===null) return null;
                $s = mb_convert_kana($s, 'asKV');
                return mb_strtolower(trim($s),'UTF-8');
            };
            $k->label_norm   = $norm($k->label);
            $k->reading_norm = $norm($k->reading);
        });
    }
}
