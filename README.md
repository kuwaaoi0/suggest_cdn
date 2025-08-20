# Suggest CDN (Laravel + JS)

A Laravel-backed, CDN-distributable suggestion library.  
Tag your `<input>` fields and get instant suggestions—no heavy setup.

日本語: Laravel 製バックエンド + CDN 配信のフロント JS で、`<input>` にタグを付けるだけでサジェスト機能を実装できます。

## Features

- 🔌 **Drop-in**: 既存フォームに 'data-suggest' 属性を付けるだけ
- 🚀 **CDN ready**: 1ファイル読み込みで即利用
- 🧠 **Search API**: Laravel 側で柔軟にデータソースを差し替え可能
- 🧩 **Framework-agnostic**: どのフロントにも組み込みやすい

## Demo (Frontend)

```html
<!-- CDN: 例。実際のURL/バージョンに置き換えてください -->
<script src="https://cdn.example.com/suggest-cdn@1.0.0/dist/suggest.min.js"></script>

<input type="text"
       data-suggest
       placeholder="Type a card name..." />

<script>
  // Initialize once after the page loads
  Suggest.init();
</script>

