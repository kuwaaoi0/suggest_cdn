---
title: "トラブルシューティング"
slug: "troubleshooting"
order: 100
---

よくある詰まりポイントと切り分け手順をまとめます。

## 1) まず疎通確認
```bash
curl -i "https://{HOST}/api/ping"
```
200 が返ればAPIへの到達はOKです。

## 2) ブラウザコンソールの確認
- CORS エラー → 許可オリジン（`allowed_origins`）の設定と一致を確認
- 401/403/429 → 下記の「ステータス別チェック」を参照

## 3) ステータス別チェック
| ステータス | 主因 | 確認ポイント |
|---|---|---|
| 401 | APIキー不備 | `<script data-api-key>` / `X-Suggest-Key` |
| 403 | 許可オリジン外 | スキーム/サブドメイン/ポート差異 |
| 429 | レート超過 | 同時多発入力・短時間連打 / `Retry-After` |

## 4) UIが表示されない
- CSSの読み込み順（上書きで非表示にしていないか）
- 親要素の `overflow:hidden` で見切れていないか
- `position` の絡みでメニューが画面外へ出ていないか

## 5) 自動送信されない／送られすぎる
- フォームに `submit` リスナーがあり `preventDefault` している可能性
- モーダル内フォームで**二重バインド**になっていないか

## 6) 変更が反映されない（本番）
```bash
php artisan view:clear
php artisan route:clear
php artisan config:clear
```
キャッシュクリアで改善するケースがあります。

## 7) 連絡時の情報
- 発生日時／再現手順
- 利用中の `site_key`, 呼び先 `api` URL
- ブラウザのコンソール出力（エラー全文）
- `curl` のレスポンスヘッダ（`Retry-After` 等）
