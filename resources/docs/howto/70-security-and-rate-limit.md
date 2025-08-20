---
title: "セキュリティ / レート制限 / オリジン許可"
slug: "security-and-rate-limit"
order: 70
---

運用で重要な「鍵の扱い」「許可オリジン」「レート制限」についての指針です。

## 鍵（Keys）
- **サイトキー**（`site_key` / `X-Site-Key`）: テナント識別。URL/ヘッダのいずれかで必須。
- **APIキー**（`X-Suggest-Key` / `<script data-api-key>`）: API呼び出しの認証に利用。
- フロントに埋めるキーは**公開前提**で発行/権限を絞る（ローテーション可能な設計を推奨）。

## 許可オリジン（CORS/Referer）
- 管理画面で **`allowed_origins`** を設定。マルチドメインは**空白区切り**等で複数登録。
- 不一致時は **403**。`https`/サブドメインの相違に注意。

## レート制限
- 既定：**1分あたり120リクエスト / IP / site**（429時 `Retry-After`）
- クライアント側は**指数バックオフ**推奨（120ms, 240ms, 480ms… 上限あり）

## 推奨ヘッダ
```http
Cache-Control: no-store
Pragma: no-cache
```
※ 候補が動的な場合。静的候補ならCDNキャッシュ方針に合わせる。

## よくある問題
| 症状 | 原因 | 対処 |
|---|---|---|
| 401 | APIキー不備 | `<script data-api-key>` か `X-Suggest-Key` を確認 |
| 403 | 許可オリジン外 | `allowed_origins` を正しく設定 |
| 429 | レート超過 | バックオフ/同時入力の制御 |
