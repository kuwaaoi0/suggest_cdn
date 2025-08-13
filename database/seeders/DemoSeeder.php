<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sites')->insert([
            'name' => 'Demo',
            'site_key' => 'demo',
            'allowed_origins' => json_encode([]), // あとで本番ドメインを入れる
            'is_active' => 1,
        ]);

        $gid = DB::table('genres')->insertGetId([
            'name' => 'デフォルト',
            'slug' => 'default',
        ]);

        foreach (['iPhone 15 Pro','iPad Air','iMac','iPad Pro','AirPods Pro','Apple Watch'] as $i => $w) {
            DB::table('keywords')->insert([
                'label' => $w,
                'reading' => null,
                'genre_id' => $gid,
                'weight' => 100 - $i,
                'is_active' => 1,
            ]);
        }
    }
}
