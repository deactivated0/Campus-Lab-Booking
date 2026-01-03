<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Auth Not Installed</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;line-height:1.5;padding:2rem}
    .box{max-width:720px;margin:0 auto;border:1px solid #e6e6e6;padding:1.25rem;border-radius:6px}
    code{background:#f7f7f8;padding:.2rem .4rem;border-radius:4px}
    .cmd{margin-top:.5rem}
  </style>
</head>
<body>
  <div class="box">
    <h1>Authentication not installed</h1>
    <p>This project does not have auth scaffolding installed. To enable login/register routes and UI, install Laravel Breeze (Inertia + Vue) or your preferred auth package.</p>

    <h3>Typical setup (Breeze)</h3>
    <p>Run these commands from the project directory:</p>
    <div class="cmd">
      <div><code>composer require laravel/breeze --dev</code></div>
      <div><code>php artisan breeze:install vue</code></div>
      <div><code>npm install && npm run dev</code></div>
      <div><code>php artisan migrate --seed</code></div>
    </div>

    <p>If you just want to continue without auth, return to the <a href="/">home page</a>.</p>
  </div>
</body>
</html>
