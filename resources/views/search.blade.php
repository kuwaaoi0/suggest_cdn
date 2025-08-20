<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="https://your-cdn.example/suggest.css">
  <input type="search" class="my-suggest" placeholder="検索">
  <script defer src="https://your-cdn.example/suggest.js"
    data-site-key="site_ujvulvvqax"
    data-api="https://api.your-domain.example/api/suggest"
    data-click="https://api.your-domain.example/api/click"
    data-api-key="vBAk3ezq3dH6FKO5-TidSNuFgB8znQ5QYhJOXw"
    data-input-class="my-suggest"
    data-open-on-focus="true"></script>
</head>
<body>
  <input type="search" class="my-suggest" placeholder="検索">

  <script defer
    src="https://api.example.com/cdn/suggest.js"
    data-site-key="YOUR_PUBLIC_SITE_KEY"
    data-input-class="my-suggest"
    data-api="https://api.example.com/api/suggest"
    data-user-token="{{ $suggestJwt }}">
  </script>
</body>
</html>
