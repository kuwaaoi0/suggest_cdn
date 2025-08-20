---
title: "data-* 属性リファレンス"
slug: "attributes"
order: 20
---

HTML の `data-*` 属性だけで挙動を調整できます。既定値と合わせて説明します。

| 属性 | 既定値 | 説明 |
|---|---|---|
| `data-site-key` | なし | 発行済みサイトキー（必須） |
| `data-api` | `/api/suggest` | サジェストAPIのURL |
| `data-click` | `/api/click` | クリック記録APIのURL（省略時は `/suggest` → `/click` に置換） |
| `data-api-key` | なし | `X-Suggest-Key` に設定する公開キー |
| `data-min-chars` | `1` | 発火最小文字数 |
| `data-debounce` | `120` | 入力反映の遅延(ms) |
| `data-open-on-focus` | `false` | フォーカス時に候補を開く |
| `data-token` / `data-token-url` | なし | JWT によるパーソナライズ |
| `data-suggest` | なし | セレクタ指定で一括バインド（例：`.js-suggest`） |
| `data-input-class` | なし | 指定クラスの `<input>` にバインド（互換用） |

### 例：フォーカス時に候補を開く
```html
<input data-suggest data-open-on-focus="true" placeholder="検索">
```

### 例：複数入力に一括バインド
```html
<script defer src="https://{HOST}/cdn/suggest.js"
  data-site-key="..."
  data-api="https://{HOST}/api/suggest"
  data-suggest=".js-suggest"></script>
<!-- ページ内の .js-suggest 全てに適用 -->
```
