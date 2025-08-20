---
title: "API と認証"
slug: "api-and-auth"
order: 30
---

このページでは、公開CDNのサジェストが呼び出す **API 仕様** と **認証（キー／オリジン／レート制限）** を説明します。

## エンドポイント

### `GET /api/suggest`
- **クエリ**:
  - `query` (string, 必須): 入力中のキーワード
  - `site_key` (string, 必須) またはヘッダ `X-Site-Key`
  - `limit` (int, 任意 / 既定: 20)
  - `u` (string, 任意): 参照URLやユーザー識別子
- **ヘッダ**:
  - `X-Suggest-Key`: 公開APIキー（`data-api-key` から付与可）
- **レスポンス例**
```json
{
  "items": [
    { "id": "kw_123", "label": "iPhone 15 Pro", "reading": "あいふぉん15ぷろ", "weight": 0.91 },
    { "id": "kw_456", "label": "iPhone ケース", "reading": "あいふぉん けーす", "weight": 0.73 }
  ],
  "took_ms": 12
}
```

### `POST /api/click`
- **目的**: 候補選択（クリック）ログの記録
- **ボディ例（JSON）**
```json
{
  "site_key": "YOUR_SITE_KEY",
  "item_id": "kw_123",
  "label": "iPhone 15 Pro",
  "query": "iphone",
  "extra": { "from": "search-form-1" }
}
```
- **ヘッダ**: `X-Suggest-Key`（推奨）

### `GET /api/ping`
疎通確認用（200が返ればOK）。

---

## 認証と制限

### キー
- **サイトキー**: `site_key` / `X-Site-Key`。テナント識別用。
- **APIキー**: `X-Suggest-Key`（CDN埋め込み時は `<script data-api-key="...">` で付与）。

### オリジン制御（CORS/Referer）
- 許可オリジンは管理で設定（`allowed_origins`）。一致しない場合は 403。

### レート制限
- 既定: **1分あたり120リクエスト / IP / site**。
- 超過時: 429 と `Retry-After` ヘッダ。

---

## リクエスト例（cURL）

```bash
curl -G "https://{HOST}/api/suggest" \
  -H "X-Site-Key: YOUR_SITE_KEY" \
  -H "X-Suggest-Key: YOUR_PUBLIC_API_KEY" \
  --data-urlencode "query=iphone" \
  --data-urlencode "limit=20"
```

```bash
curl -X POST "https://{HOST}/api/click" \
  -H "Content-Type: application/json" \
  -H "X-Suggest-Key: YOUR_PUBLIC_API_KEY" \
  -d '{
    "site_key":"YOUR_SITE_KEY",
    "item_id":"kw_123",
    "label":"iPhone 15 Pro",
    "query":"iphone"
  }'
```

---

## エラーと対処
| ステータス | 代表例 | 対処 |
|---|---|---|
| 401 | APIキー不備 | `X-Suggest-Key` または `<script data-api-key>` を確認 |
| 403 | 許可オリジン外 | `allowed_origins` を設定／一致確認 |
| 429 | レート超過 | `Retry-After` 待機・指数バックオフ |
| 5xx | 一時障害 | リトライポリシーの実装 |

---
