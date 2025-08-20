<?php

namespace App\Filament\Imports;

use App\Models\Genre;
use App\Models\Keyword;
use App\Models\KeywordAlias;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;

class KeywordImporter extends Importer
{
    protected static ?string $model = Keyword::class;

    /** 事前オプションUI：ユーザー単位にするので不要 */
    public static function getOptionsFormComponents(): array
    {
        return [];
    }

    /** 受け付けるCSVの列 */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('label')
                ->requiredMapping()
                ->rules(['required','string','max:255']),

            ImportColumn::make('reading')
                ->rules(['nullable','string','max:255']),

            ImportColumn::make('genre')
                ->rules(['nullable','string','max:255']),

            // 小数の可能性があるなら numeric。整数限定なら integer のままでもOK
            ImportColumn::make('weight')
                ->rules(['nullable','numeric']),

            // true/false/1/0/yes/no などを受けたい場合はキャストを付けると安定
            ImportColumn::make('is_active')
                ->rules(['nullable'])
                ->castStateUsing(fn ($state) => match (strtolower(trim((string) $state))) {
                    '1','true','yes','y','on' => 1,
                    '0','false','no','n','off','' => 0,
                    default => is_numeric($state) ? (int) $state : 0,
                }),

            ImportColumn::make('aliases')
                ->rules(['nullable','string']),
        ];
    }

    public function resolveRecord(): ?Keyword
    {
        $userId = Auth::id();
        $label  = (string) $this->data['label'];
        $norm   = fn($s)=> mb_strtolower(trim(mb_convert_kana((string)$s,'asKV')), 'UTF-8');

        return Keyword::query()
            ->where('user_id', $userId)
            ->where('label_norm', $norm($label))
            ->first() ?? new Keyword();
    }

    /** v3 は引数なし */
    public function fillRecord(): void
    {
        $record = $this->record ??= new Keyword();

        $genreId = null;
        if (!empty($this->data['genre'])) {
            $genreName = (string) $this->data['genre'];
            // ユーザー単位でジャンルを作成/取得
            $genreId = Genre::firstOrCreate([
                'user_id' => Auth::id(),
                'name'    => $genreName,
            ])->id;
        }

        $record->user_id   = Auth::id();
        $record->genre_id  = $genreId;
        $record->label     = (string) $this->data['label'];
        $record->reading   = $this->data['reading'] ?? null;
        $record->weight    = (int) ($this->data['weight'] ?? 0);
        $record->is_active = (bool) ($this->data['is_active'] ?? true);
    }

    /** 保存後：エイリアス投入 */
    protected function afterSave(): void
    {
        $aliases = array_filter(array_map('trim', explode(';', (string)($this->data['aliases'] ?? ''))));
        if (!$aliases) return;

        foreach ($aliases as $a) {
            KeywordAlias::firstOrCreate([
                'keyword_id' => $this->record->id,
                'alias'      => $a,
            ]);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return "キーワードのインポートが完了: 成功 {$import->successful_rows} / 失敗 {$import->getFailedRowsCount()}";
    }
}

