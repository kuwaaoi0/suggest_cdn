<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\OwnedByUser;

class Keyword extends Model
{

    use OwnedByUser;

    protected $fillable = ['user_id','label','reading','genre_id','weight','is_active','label_norm','reading_norm'];
    public function user(){ return $this->belongsTo(\App\Models\User::class); }
    public function genre(){ return $this->belongsTo(Genre::class); }

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

    public function aliases(){ return $this->hasMany(\App\Models\KeywordAlias::class); }
}
