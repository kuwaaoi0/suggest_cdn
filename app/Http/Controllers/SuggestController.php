<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Keyword;
use App\Models\KeywordAlias;
use App\Models\UserProfile;
use App\Models\UserKeywordPref;
use App\Services\SuggestToken;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SuggestController extends Controller
{
    /**
     * GET /api/suggest
     *
     * Query params:
     *  - query     string  required  検索文字列（1〜100）
     *  - site_key  string  required  サイト識別キー
     *  - u         string  optional  ユーザー識別用JWT（またはヘッダ X-User-Token）
     *  - limit     int     optional  返却件数（1〜20, 既定20）
     */
    public function __invoke(Request $req): JsonResponse
    {
        $started = microtime(true);

        $q       = trim((string) $req->query('query', ''));
	$siteKey = (string) (
    		$req->query('site_key')
    		?? $req->input('site_key')
    		?? $req->header('X-Site-Key')
    		?? ''
	);

    	if ($siteKey === '') {
        	$raw = $req->getContent();
        	if (is_string($raw) && $raw !== '') {
            	try {
                	$json = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                	if (is_array($json) && isset($json['site_key'])) {
                    	$siteKey = (string) $json['site_key'];
                	}
            	} catch (\Throwable $e) {
                	// スルー
            	}
        	}
    	}

    	abort_if($siteKey === '', 400, 'site_key required');

        $limit   = (int) $req->query('limit', 20);
        $limit   = max(1, min(20, $limit)); // 1..20 に丸め

        // 入力チェック
        abort_if($siteKey === '', 403, 'site_key required');
        abort_if(strlen($q) < 1 || strlen($q) > 100, 422, 'invalid query length');

        // サイト検証
        $site = Site::where('site_key', $siteKey)->where('is_active', true)->first();
        abort_if(!$site, 403, 'invalid site');

	$ownerUserId = $site->users()->wherePivot('role', 'owner')->value('users.id')
		?? $site->users()->value('users.id');
	abort_if(!$ownerUserId, 403, 'no owner for site');


        // ユーザー識別（短命JWT）
        $userToken   = (string) $req->query('u', $req->header('X-User-Token', ''));
        // $userInfo    = SuggestToken::decode($userToken);
        $userInfo = SuggestToken::decodeForSite($userToken, $site);
        $userProfile = null;
        if ($userInfo && !empty($userInfo['external_user_id'])) {
            $userProfile = UserProfile::firstOrCreate([
                'site_id'          => $site->id,
                'external_user_id' => (string) $userInfo['external_user_id'],
            ]);
        }

        // 正規化（全角→半角/カナ→かな 等 + 小文字化）
        $normalize = static function (string $s): string {
            $s = mb_convert_kana($s, 'asKV');
            return mb_strtolower(trim($s), 'UTF-8');
        };
        $qNorm = $normalize($q);

        // ---------- 1) Keyword 直接ヒット ----------
        $base = Keyword::query()
	    ->where('user_id', $ownerUserId)
	    ->where('is_active', true)
            ->where(function ($w) use ($qNorm) {
                $w->where('label_norm',   'like', $qNorm.'%')        // 前方一致（速い）
                  ->orWhere('label_norm', 'like', '% '.$qNorm.'%')   // 単語境界の部分一致
                  ->orWhere('reading_norm','like', $qNorm.'%');      // 読み前方一致
            })
            ->with('genre')
            ->orderByRaw(
                // 簡易スコア：完全一致>前方一致>単語境界一致>読み一致
                "CASE
                    WHEN label_norm = ?      THEN 100
                    WHEN label_norm LIKE ?   THEN 90
                    WHEN label_norm LIKE ?   THEN 80
                    WHEN reading_norm LIKE ? THEN 70
                    ELSE 0
                 END DESC",
                [$qNorm, $qNorm.'%', '% '.$qNorm.'%', $qNorm.'%']
            )
            ->orderByDesc('weight')                 // 手動重み
            ->orderByRaw('CHAR_LENGTH(label) ASC')  // 短い語を少し優先
            ->limit(50)                             // まず多めに集める
            ->get();

        $items = $base->map(fn ($k) => [
            'id'          => $k->id,
            'label'       => $k->label,
            'genre'       => $k->genre?->name,
            'base_weight' => (int) $k->weight,
            // '_alias' フラグは直ヒットでは不要
        ])->all();

        // ---------- 2) 別名（KeywordAlias）ヒットを追加 ----------
        $aliasHits = KeywordAlias::query()
	    ->whereHas('keyword', fn($q) => $q->where('user_id', $ownerUserId)->where('is_active', true))
            ->where('alias_norm', 'like', $qNorm.'%')     // 別名は前方一致で
            ->limit(50)
            ->with(['keyword' => function ($q) {
                $q->where('is_active', true)->with('genre');
            }])
            ->get()
            ->map(function ($a) {
                $k = $a->keyword;
                if (!$k) return null;
                return [
                    'id'          => $k->id,
                    'label'       => $k->label,            // 表示は正規ラベル
                    'genre'       => $k->genre?->name,
                    'base_weight' => (int) $k->weight,
                    '_alias'      => true,                 // 別名経由ヒット
                ];
            })
            ->filter()
            ->all();

        // 直ヒット + 別名ヒットをマージ（同一IDは統合）
        $byId = [];
        foreach (array_merge($items, $aliasHits) as $it) {
            $id = $it['id'];
            if (!isset($byId[$id])) {
                $byId[$id] = $it;
            } else {
                // どちらかが別名ヒットならフラグ立て
                $byId[$id]['_alias'] = ($byId[$id]['_alias'] ?? false) || ($it['_alias'] ?? false);
            }
        }
        $items = array_values($byId);

        // ---------- 3) ユーザー別プリファレンス適用 ----------
        if ($userProfile && $items) {
            $ids = array_column($items, 'id');

            $prefs = UserKeywordPref::where('user_profile_id', $userProfile->id)
                ->whereIn('keyword_id', $ids)
                ->get()
                ->keyBy('keyword_id');

            $scored = [];
            foreach ($items as $it) {
                $pref = $prefs[$it['id']] ?? null;

                // 強制非表示
                if ($pref && $pref->visibility === 'force_hide') {
                    continue;
                }

                // 基本スコア：0.5 + weight/100
                $score = 0.5 + (int)($it['base_weight'] ?? 0) / 100.0;

                // 別名経由の軽い加点（任意：0.05）
                if (!empty($it['_alias'])) {
                    $score += 0.05;
                }

                // 強制表示/ブースト
                if ($pref) {
                    if ($pref->visibility === 'force_show') {
                        $score += 1.0;
                    }
                    $score += ((int) $pref->boost) / 100.0; // -100〜+100 を想定
                }

                $it['score'] = round($score, 4);
                $scored[] = $it;
            }

            usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
            $items = array_slice($scored, 0, $limit);
        } else {
            // ユーザー情報なし：weight優先（別名加点は無視）
            usort($items, fn ($a, $b) => ($b['base_weight'] ?? 0) <=> ($a['base_weight'] ?? 0));
            $items = array_slice($items, 0, $limit);
        }

        // 返却整形
        $payloadItems = array_map(
            fn ($i) => [
                'id'    => $i['id'],
                'label' => $i['label'],
                'genre' => $i['genre'],
                'score' => $i['score'] ?? null,
            ],
            $items
        );

        return response()->json([
            'q'    => $q,
            'items'=> $payloadItems,
            'meta' => [
                'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                'count'      => count($payloadItems),
            ],
        ]);
    }
}
