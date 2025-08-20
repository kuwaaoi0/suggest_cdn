---
title: "CDNで最短導入（Quick Start）"
slug: "quickstart"
order: 10
---

このページは **自分のサイトに検索サジェストを導入するユーザー**向けの最短手順です。コピペしてそのまま使えます。

## 1) CSS/JS を読み込む
あなたのサイトの `<head>` に次を追加します。

```html
<link rel="stylesheet" href="https://xn--hck1ajf9e6666bhk3a.com/cdn/suggest.css">
<script defer src="https://xn--hck1ajf9e6666bhk3a.com/cdn/suggest.js"
  data-site-key="YOUR_PUBLIC_SITE_KEY"
  data-api="https://xn--hck1ajf9e6666bhk3a.com/api/suggest"
  data-click="https://xn--hck1ajf9e6666bhk3a.com/api/click"
  data-api-key="YOUR_PUBLIC_API_KEY"
  data-input-class="my-suggest"
  data-open-on-focus="true"></script>
```

## 2) 入力を設置
```html
<input type="search" data-suggest placeholder="商品名を検索">
```

## 3) 疎通確認
`GET /api/ping` が 200 を返せばOK。CORS・鍵・オリジン許可を確認してください。

## 4) よくある詰まり
- 429: レート制限。`Retry-After` を確認
- 403: `allowed_origins` 未設定/不一致
- 401: `X-Suggest-Key`（または `data-api-key`）の不備
