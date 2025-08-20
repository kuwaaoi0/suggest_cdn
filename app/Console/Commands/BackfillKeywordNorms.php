<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Keyword;

class BackfillKeywordNorms extends Command
{
    protected $signature = 'keywords:backfill-norms {--chunk=500}';
    protected $description = 'Backfill label_norm and reading_norm for existing keywords';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');

        // 文字のゆらぎを吸収：全角→半角/カタカナ→ひらがな 等 + 小文字化 + trim
        $norm = function (?string $s): ?string {
            if ($s === null) return null;
            $s = mb_convert_kana($s, 'asKV');
            return mb_strtolower(trim($s), 'UTF-8');
        };

        $total = Keyword::count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Keyword::query()
            ->select('id','label','reading')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use ($norm, $bar) {
                foreach ($rows as $k) {
                    $k->label_norm   = $norm($k->label);
                    $k->reading_norm = $norm($k->reading);
                    $k->saveQuietly(); // 余計なイベントを発火させない
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info('Backfill completed.');

        return self::SUCCESS;
    }
}
