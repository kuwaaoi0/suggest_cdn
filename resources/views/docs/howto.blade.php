<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title }} | How To | {{ config('app.name') }}</title>
  @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  @endif
  <style>
    :root{
      --bg:#ffffff; --fg:#0f172a; --muted:#64748b; --border:#e2e8f0;
      --accent:#2563eb; --accent-weak:#eff6ff; --code-bg:#0b1220; --code-fg:#e2e8f0;
      --card:#ffffff;
    }
    @media (prefers-color-scheme: dark){
      :root{
        --bg:#0b1220; --fg:#e5e7eb; --muted:#94a3b8; --border:#1e293b;
        --accent:#60a5fa; --accent-weak:#0b2a4a; --code-bg:#0a0f1c; --code-fg:#e5e7eb;
        --card:#0f172a;
      }
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0; background:var(--bg); color:var(--fg);
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, "Hiragino Kaku Gothic ProN", Meiryo, "Noto Sans JP", sans-serif;
      -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;
    }
    .wrap{display:grid; grid-template-columns:260px 1fr; min-height:100vh}
    aside{
      border-right:1px solid var(--border); background:var(--card);
      padding:20px; position:sticky; top:0; height:100vh; overflow:auto;
    }
    .brand{font-weight:700; letter-spacing:.2px; opacity:.85; margin-bottom:14px}
    .nav{display:flex; flex-direction:column; gap:4px}
    .nav a{
      display:flex; align-items:center; gap:8px;
      padding:8px 10px; border-radius:10px; color:var(--fg);
      text-decoration:none; transition:background .15s ease, color .15s ease;
    }
    .nav a:hover{ background:var(--accent-weak) }
    .nav a.active{ background:var(--accent-weak); font-weight:600; }
    .dot{width:6px;height:6px;border-radius:999px;background:var(--muted);opacity:.8}
    .nav a.active .dot{ background:var(--accent) }

    main{padding:32px 28px; max-width: 980px}
    h1{font-size:28px; line-height:1.2; font-weight:800; margin:0 0 10px}
    .subtitle{color:var(--muted); font-size:14px; margin-bottom:18px}
    h2{font-size:20px; margin:28px 0 10px}
    p{line-height:1.9; margin:10px 0}
    ul,ol{padding-left:1.2rem}
    code, pre{font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, Monaco, "Noto Sans Mono", monospace}
    pre{
      background:var(--code-bg); color:var(--code-fg);
      border:1px solid var(--border); border-radius:12px; padding:14px; overflow:auto;
    }
    code:not(pre code){
      background:rgba(148,163,184,.15); padding:.15rem .4rem; border-radius:6px;
    }
    table{width:100%; border-collapse:separate; border-spacing:0; margin:10px 0 18px}
    th,td{padding:10px 12px; border-bottom:1px solid var(--border); text-align:left}
    th{font-weight:600; color:var(--muted)}
    .card{
      background:var(--card); border:1px solid var(--border);
      border-radius:14px; padding:18px; margin:16px 0;
    }
    @media (max-width: 920px){
      .wrap{grid-template-columns: 1fr}
      aside{position:relative; height:auto; border-right:none; border-bottom:1px solid var(--border)}
    }
  </style>
</head>
<body>
  <div class="wrap">
    <aside>
      <div class="brand">How To</div>
      <nav class="nav">
        @foreach($menu as $item)
          <a href="{{ url('/docs/how-to/'.$item['slug']) }}" class="{{ $active===$item['slug'] ? 'active' : '' }}">
            <span class="dot" aria-hidden="true"></span>
            <span>{{ $item['title'] }}</span>
          </a>
        @endforeach
      </nav>
    </aside>
    <main>
      <h1>{{ $title }}</h1>
      <div class="subtitle">{{ config('app.name') }} の導入・使い方ガイド</div>
      {!! $html !!}
    </main>
  </div>
</body>
</html>
