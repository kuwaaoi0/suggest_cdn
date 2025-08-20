<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) user_id を追加（未存在時のみ）
        foreach (['genres', 'keywords', 'keyword_aliases'] as $table) {
            if (! Schema::hasColumn($table, 'user_id')) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    // users テーブルを参照。別名の場合は ->constrained('users') を修正してください
                    $t->foreignId('user_id')->after('id')->constrained()->cascadeOnDelete();
                    $t->index('user_id');
                });
            }
        }

        // 2) 複合ユニーク（存在チェック＆列チェック付き）

        // genres: (user_id, name)
        if (Schema::hasColumn('genres', 'name')) {
            $idx = 'genres_user_id_name_unique';
            if (! $this->indexExists('genres', $idx)) {
                Schema::table('genres', function (Blueprint $t) use ($idx) {
                    $t->unique(['user_id', 'name'], $idx);
                });
            }
        }

        // keywords: (user_id, <候補列>)
        $kwCol = $this->firstExistingColumn('keywords', ['name', 'keyword', 'title', 'value', 'label']);
        if ($kwCol !== null) {
            $idx = "keywords_user_id_{$kwCol}_unique";
            if (! $this->indexExists('keywords', $idx)) {
                Schema::table('keywords', function (Blueprint $t) use ($kwCol, $idx) {
                    $t->unique(['user_id', $kwCol], $idx);
                });
            }
        }

        // keyword_aliases: (user_id, <候補列>)
        $kaCol = $this->firstExistingColumn('keyword_aliases', ['alias', 'name', 'value', 'label']);
        if ($kaCol !== null) {
            $idx = "keyword_aliases_user_id_{$kaCol}_unique";
            if (! $this->indexExists('keyword_aliases', $idx)) {
                Schema::table('keyword_aliases', function (Blueprint $t) use ($kaCol, $idx) {
                    $t->unique(['user_id', $kaCol], $idx);
                });
            }
        }
    }

    public function down(): void
    {
        // genres のユニークを削除→user_id を削除
        if (Schema::hasColumn('genres', 'user_id')) {
            $idx = 'genres_user_id_name_unique';
            if ($this->indexExists('genres', $idx)) {
                Schema::table('genres', function (Blueprint $t) use ($idx) {
                    $t->dropUnique($idx);
                });
            }
            Schema::table('genres', function (Blueprint $t) {
                try {
                    $t->dropConstrainedForeignId('user_id');
                } catch (\Throwable $e) {
                    if (Schema::hasColumn('genres', 'user_id')) {
                        $t->dropColumn('user_id');
                    }
                }
            });
        }

        // keywords のユニークを削除→user_id を削除
        $kwCol = $this->firstExistingColumn('keywords', ['name', 'keyword', 'title', 'value', 'label']);
        if (Schema::hasColumn('keywords', 'user_id')) {
            if ($kwCol !== null) {
                $idx = "keywords_user_id_{$kwCol}_unique";
                if ($this->indexExists('keywords', $idx)) {
                    Schema::table('keywords', function (Blueprint $t) use ($idx) {
                        $t->dropUnique($idx);
                    });
                }
            }
            Schema::table('keywords', function (Blueprint $t) {
                try {
                    $t->dropConstrainedForeignId('user_id');
                } catch (\Throwable $e) {
                    if (Schema::hasColumn('keywords', 'user_id')) {
                        $t->dropColumn('user_id');
                    }
                }
            });
        }

        // keyword_aliases のユニークを削除→user_id を削除
        $kaCol = $this->firstExistingColumn('keyword_aliases', ['alias', 'name', 'value', 'label']);
        if (Schema::hasColumn('keyword_aliases', 'user_id')) {
            if ($kaCol !== null) {
                $idx = "keyword_aliases_user_id_{$kaCol}_unique";
                if ($this->indexExists('keyword_aliases', $idx)) {
                    Schema::table('keyword_aliases', function (Blueprint $t) use ($idx) {
                        $t->dropUnique($idx);
                    });
                }
            }
            Schema::table('keyword_aliases', function (Blueprint $t) {
                try {
                    $t->dropConstrainedForeignId('user_id');
                } catch (\Throwable $e) {
                    if (Schema::hasColumn('keyword_aliases', 'user_id')) {
                        $t->dropColumn('user_id');
                    }
                }
            });
        }
    }

    /**
     * 指定テーブルに index_name のインデックスが存在するか（MySQL）
     */
    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->whereRaw('table_schema = DATABASE()')
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    /**
     * 候補配列のうち、最初に存在するカラム名を返す。無ければ null
     */
    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (Schema::hasColumn($table, $c)) {
                return $c;
            }
        }
        return null;
    }
};
