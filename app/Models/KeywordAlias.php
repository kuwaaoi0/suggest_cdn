<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\OwnedByUser;

class KeywordAlias extends Model
{
    use OwnedByUser;

    protected $fillable = ['keyword_id','alias','alias_norm'];

    protected static function booted()
    {
        static::saving(function (self $a) {
            $a->alias_norm = self::normalize($a->alias);
        });
    }

    public static function normalize(?string $s): ?string
    {
        if ($s === null) return null;
        $s = mb_convert_kana($s, 'asKV');
        return mb_strtolower(trim($s), 'UTF-8');
    }

    public function keyword(){ return $this->belongsTo(\App\Models\Keyword::class); }
}
