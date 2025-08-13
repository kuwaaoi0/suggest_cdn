<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Site;

class SitesBackfillKeys extends Command
{
    protected $signature = 'sites:backfill-keys {--force : 既存値も強制再発行}';
    protected $description = 'Generate api_key / jwt_secret for sites';

    public function handle(): int
    {
        $force = (bool)$this->option('force');

        $gen = fn($len=32) => rtrim(strtr(base64_encode(random_bytes($len)), '+/', '-_'), '=');

        $n=0;
        foreach (Site::cursor() as $s) {
            $changed = false;
            if ($force || empty($s->api_key))   { $s->api_key   = $gen(28); $changed = true; }
            if ($force || empty($s->jwt_secret)){ $s->jwt_secret= $gen(48); $changed = true; }
            if (empty($s->jwt_issuer))          { $s->jwt_issuer= 'suggest-sa'; $changed = true; }
            if ($changed) { $s->saveQuietly(); $n++; }
        }
        $this->info("Updated: {$n}");
        return self::SUCCESS;
    }
}
