<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeywordClick extends Model
{
    protected $table = 'keyword_clicks';
    // created_at / updated_at がある前提。無いなら下行を有効化:
    // public $timestamps = false;
}
