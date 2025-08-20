<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserKeywordAlias extends Model
{
    protected $fillable = ['user_keyword_id','alias','alias_norm'];

    public function keyword(){ return $this->belongsTo(UserKeyword::class, 'user_keyword_id'); }

    protected static function booted()
    {
        static::saving(function(self $a){
            if ($a->alias !== null) {
                $s = mb_convert_kana($a->alias, 'asKV');
                $a->alias_norm = mb_strtolower(trim($s),'UTF-8');
            }
        });
    }
}
