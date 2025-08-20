<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Keyword;

class KeywordsDecay extends Command
{
    protected $signature = 'keywords:decay
        {--rate=1 : 減衰率(%) 例: 1 = 1%}
        {--floor=0 : 下限weight}
        {--chunk=1000 : 更新チャンク件数}
        {--dry : 変更せず試算のみ}';

    protected $description = 'Reduce keyword weights by a daily decay rate.';

    public function handle(): int
    {
        $rate  = max(0.0, (float)$this->option('rate'));   // %
        $floor = max(0,   (int)$this->option('floor'));
        $chunk = max(100, (int)$this->option('chunk'));
        $dry   = (bool)$this->option('dry');

        $factor = 1.0 - ($rate / 100.0);
        $this->info(sprintf('Decay: -%.2f%% (factor=%.4f), floor=%d, dry=%s', $rate, $factor, $floor, $dry ? 'yes':'no'));

        $total = Keyword::count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $changed = 0;
        Keyword::query()->select('id','weight')->orderBy('id')->chunkById($chunk, function($rows) use ($factor, $floor, $dry, &$changed, $bar){
            foreach ($rows as $k) {
                $new = (int)floor($k->weight * $factor);
                if ($new < $floor) $new = $floor;
                if ($new !== (int)$k->weight) {
                    $changed++;
                    if (!$dry) {
                        $k->weight = $new;
                        $k->saveQuietly();
                    }
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Affected rows: {$changed}" . ($dry ? ' (dry-run)':''));

        return self::SUCCESS;
    }
}
