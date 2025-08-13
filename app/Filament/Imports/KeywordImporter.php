<?php

namespace App\Filament\Importers;

use App\Models\Genre;
use App\Models\Keyword;
use App\Models\KeywordAlias;
use App\Models\Site;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;

class KeywordImporter extends Importer
{
    protected static ?string $model = Keyword::class;

    /** インポート前に表示するオプションUI（ここでサイトを選べます） */
    public static function getOptionsFormComponents(): array
    {
        return [
            Forms\Components\Select::make('site_id')
                ->label('Import into site')
                ->options(fn () => Auth::user()
                    ? Auth::user()->sites()->pluck('name', 'sites.id')->toArray()
                    : []
                )
                ->default(fn () => (int) session('current_site_id') ?: (Auth::user()?->sites()->value('sites.id')))
                ->helperText('ここで選んだサイトに登録します。CSVの site_key が指定されている行は優先されます（所属外の site_key は無視して current_site を適用）。')
                ->required(),

            Forms\Components\Toggle::make('as_shared')
                ->label('共有レコードとして登録（site_id を NULL にする）')
                ->helperText('全サイト共通の辞書として扱いたい場合のみON。通常はOFF推奨。'),
        ];
    }

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('site_key')
                ->label('site_key（任意）')
                ->rules(['nullable','string']),

            ImportColumn::make('label')
                ->required()
                ->rules(['string','max:255']),

            ImportColumn::make('reading')
                ->rules(['nullable','string','max:255']),

            ImportColumn::make('genre')
                ->rules(['nullable','string','max:255']),

            ImportColumn::make('weight')
                ->rules(['nullable','integer']),

            ImportColumn::make('is_active')
                ->rules(['nullable','boolean']),

            ImportColumn::make('aliases')
                ->label('aliases（; 区切り）')
                ->rules(['nullable','string']),
        ];
    }

    /** current_site / options / CSV の順で site を決定 */
    protected function resolveSiteId(): ?int
    {
        // 1) 共有モードなら NULL
        if (!empty($this->options['as_shared'])) {
            return null;
        }

        // 2) オプションで選ばれたサイト
        $optSiteId = isset($this->options['site_id']) ? (int)$this->options['site_id'] : null;

        // 3) CSVの site_key があれば優先（ただし所属外は無視）
        $fromCsv = (string)($this->data['site_key'] ?? '');
        if ($fromCsv !== '') {
            $csvSiteId = Site::where('site_key', $fromCsv)->value('id');
            if ($csvSiteId && Auth::user()?->sites()->where('sites.id', $csvSiteId)->exists()) {
                return (int)$csvSiteId;
            }
        }

        // 4) 所属外 / 未指定ならオプション値 or current_site
        return $optSiteId ?: ((int) session('current_site_id') ?: (Auth::user()?->sites()->value('sites.id')));
    }

    public function resolveRecord(): ?Keyword
    {
        $siteId = $this->resolveSiteId();

        $label = (string)$this->data['label'];
        $norm  = fn($s)=> mb_strtolower(trim(mb_convert_kana((string)$s,'asKV')), 'UTF-8');

        return Keyword::query()
            ->when($siteId !== null, fn($q)=> $q->where('site_id', $siteId), fn($q)=> $q->whereNull('site_id'))
            ->where('label_norm', $norm($label))
            ->first() ?? new Keyword();
    }

    /** v3 は引数なし */
    public function fillRecord(): void
    {
        $record = $this->record ??= new Keyword();
        $siteId = $this->resolveSiteId();

        $genreId = null;
        if (!empty($this->data['genre'])) {
            $genreId = Genre::firstOrCreate(['name' => (string)$this->data['genre']])->id;
        }

        $record->site_id   = $siteId; // 共有ならNULL
        $record->genre_id  = $genreId;
        $record->label     = (string)$this->data['label'];
        $record->reading   = $this->data['reading'] ?? null;
        $record->weight    = (int)($this->data['weight'] ?? 0);
        $record->is_active = (bool)($this->data['is_active'] ?? true);
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
        return "共有辞書のインポートが完了: 成功 {$import->successful_rows} / 失敗 {$import->getFailedRowsCount()}";
    }
}
