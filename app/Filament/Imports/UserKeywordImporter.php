<?php

namespace App\Filament\Imports;

use App\Models\Genre;
use App\Models\Site;
use App\Models\UserProfile;
use App\Models\UserKeyword;
use App\Models\UserKeywordAlias;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;

class UserKeywordImporter extends Importer
{
    protected static ?string $model = UserKeyword::class;

    /** current_site を UI で選択（既定＝current_site） */
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
                ->required()
                ->helperText('ユーザー辞書は共有不可のため、必ずサイトに紐づけます。CSVの site_key が所属外の場合はここで選択したサイトに自動置換します。'),
        ];
    }

    public static function getColumns(): array
    {
        return [
            // 列のマッピング必須＋値も必須
            ImportColumn::make('external_user_id')
                ->requiredMapping()
                ->rules(['required','string','max:255']),

            ImportColumn::make('label')
                ->requiredMapping()
                ->rules(['required','string','max:255']),

            ImportColumn::make('reading')
                ->rules(['nullable','string','max:255']),

            ImportColumn::make('genre')
                ->rules(['nullable','string','max:255']),

            ImportColumn::make('weight')
                ->rules(['nullable','integer']),

            ImportColumn::make('visibility')
                ->rules(['nullable','in:default,force_show,force_hide']),

            ImportColumn::make('boost')
                ->rules(['nullable','integer']),

            ImportColumn::make('is_active')
                ->rules(['nullable','boolean']),

            ImportColumn::make('aliases')
                ->label('aliases（; 区切り）')
                ->rules(['nullable','string']),
        ];
    }

    protected function resolveSiteId(): int
    {
        // オプション選択を優先
        $optSiteId = isset($this->options['site_id']) ? (int)$this->options['site_id'] : null;

        // CSV site_key が所属内ならそれを優先
        $fromCsv = (string)($this->data['site_key'] ?? '');
        if ($fromCsv !== '') {
            $csvSiteId = Site::where('site_key', $fromCsv)->value('id');
            if ($csvSiteId && Auth::user()?->sites()->where('sites.id', $csvSiteId)->exists()) {
                return (int)$csvSiteId;
            }
        }

        return $optSiteId ?: ((int) session('current_site_id') ?: (Auth::user()?->sites()->value('sites.id')));
    }

    public function resolveRecord(): ?UserKeyword
    {
        $siteId = $this->resolveSiteId();

        // ユーザー（外部ID）特定
        $ext = (string)($this->data['external_user_id'] ?? '');
        $user = UserProfile::firstOrCreate(['site_id'=>$siteId, 'external_user_id'=>$ext]);

        $label = (string)($this->data['label'] ?? '');
        $norm  = fn($s)=> mb_strtolower(trim(mb_convert_kana((string)$s,'asKV')), 'UTF-8');

        return UserKeyword::query()
            ->where('site_id', $siteId)
            ->where('user_profile_id', $user->id)
            ->where('label_norm', $norm($label))
            ->first() ?? new UserKeyword();
    }

    /** v3 は引数なし */
    public function fillRecord(): void
    {
        $record = $this->record ??= new UserKeyword();

        $siteId = $this->resolveSiteId();
        $ext    = (string)($this->data['external_user_id'] ?? '');
        $user   = UserProfile::firstOrCreate(['site_id'=>$siteId, 'external_user_id'=>$ext]);

        $genreId = null;
        if (!empty($this->data['genre'])) {
            $genreId = Genre::firstOrCreate(['name' => (string)$this->data['genre']])->id;
        }

        $record->user_profile_id = $user->id;
        $record->site_id         = $siteId;
        $record->genre_id        = $genreId;
        $record->label           = (string)$this->data['label'];
        $record->reading         = $this->data['reading'] ?? null;
        $record->weight          = (int)($this->data['weight'] ?? 0);
        $record->visibility      = in_array(($this->data['visibility'] ?? 'default'), ['default','force_show','force_hide'], true)
                                    ? (string)$this->data['visibility'] : 'default';
        $record->boost           = (int)($this->data['boost'] ?? 0);
        $record->is_active       = (bool)($this->data['is_active'] ?? true);
    }

    protected function afterSave(): void
    {
        $aliases = array_filter(array_map('trim', explode(';', (string)($this->data['aliases'] ?? ''))));
        if (!$aliases) return;

        foreach ($aliases as $a) {
            UserKeywordAlias::firstOrCreate([
                'user_keyword_id' => $this->record->id,
                'alias' => $a,
            ]);
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return "ユーザー辞書のインポートが完了: 成功 {$import->successful_rows} / 失敗 {$import->getFailedRowsCount()}";
    }
}
