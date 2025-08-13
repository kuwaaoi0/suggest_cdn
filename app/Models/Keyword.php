<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keyword extends Model
{
    protected $fillable = ['label','reading','genre_id','weight','is_active','label_norm','reading_norm'];
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
