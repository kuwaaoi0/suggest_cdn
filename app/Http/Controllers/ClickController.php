<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Keyword;
use App\Models\UserProfile;
use App\Services\SuggestToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ClickController extends Controller
{
    public function __invoke(Request $req)
    {
        // JSON/FORM どちらでも受ける
        $payload = $req->all();
    	$siteKey = (string) (
        	$req->input('site_key')             // JSON body / form
        	?? $req->query('site_key')          // URL query（今回追加した保険）
        	?? $req->header('X-Site-Key')       // 予備（使う場合）
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
                	// 何もしない（JSONでなければスルー）
            	}
        	}
    	}

    	abort_if($siteKey === '', 400, 'site_key required');

        $keywordId  = (int)   ($payload['keyword_id'] ?? 0);
        $userToken  = (string)($payload['u'] ?? $req->header('X-User-Token', ''));

        abort_if($siteKey === '' || $keywordId <= 0, 422, 'invalid payload');

        $site = Site::where('site_key', $siteKey)->where('is_active', true)->first();
        abort_if(!$site, 403, 'invalid site');

        $keyword = Keyword::where('id', $keywordId)->where('is_active', true)->first();
        abort_if(!$keyword, 404, 'keyword not found');

        // ユーザー特定（任意）
        $profile = null;
        if ($userToken) {
            //$userInfo = SuggestToken::decode($userToken);
            $userInfo = SuggestToken::decodeForSite($userToken, $site);
            if ($userInfo && !empty($userInfo['external_user_id'])) {
                $profile = UserProfile::firstOrCreate([
                    'site_id' => $site->id,
                    'external_user_id' => (string)$userInfo['external_user_id'],
                ]);
            }
        }

        // 日次ログに集計
        $day = now()->toDateString();
        DB::beginTransaction();
        try {
            // 既存行をインクリメント（なければ作成→再インクリメント）
            $affected = DB::table('keyword_clicks')->where([
                'site_id' => $site->id,
                'keyword_id' => $keyword->id,
                'user_profile_id' => $profile?->id,
                'day' => $day,
            ])->increment('count');

            if ($affected === 0) {
                DB::table('keyword_clicks')->insert([
                    'site_id' => $site->id,
                    'keyword_id' => $keyword->id,
                    'user_profile_id' => $profile?->id,
                    'day' => $day,
                    'count' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 今日の weight 自動加点（1キーワードあたり上限 N回）
            $CAP = 10; // ←好みで
            $cacheKey = sprintf('kwinc:%d:%d:%s', $site->id, $keyword->id, $day);
            $n = Cache::increment($cacheKey);
            if ($n === 1) {
                Cache::put($cacheKey, 1, now()->endOfDay()); // 有効期限を当日終わりに
            }
            if ($n <= $CAP) {
                Keyword::whereKey($keyword->id)->increment('weight', 1);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            // ログだけ残して 204 は返す（UX優先）
            report($e);
        }

        return response()->noContent(); // 204
    }
}
