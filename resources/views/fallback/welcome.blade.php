<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>{{ config('app.name', 'Laravel') }}</title>
	<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
	<main style="max-width:900px;margin:4rem auto;padding:1rem;">
		<h1>{{ config('app.name') }} — Minimal fallback</h1>
		<p>The front-end assets (Vite) are not available, so the full Inertia UI cannot be loaded.</p>
		<p>To restore the full SPA UI run:</p>
		<pre>npm install && npm run dev</pre>
		<p>Or to generate production assets:</p>
		<pre>npm run build</pre>

		@if($canLogin)
			<p><a href="{{ route('login') }}">Login</a></p>
		@endif

		@if($canRegister)
			<p><a href="{{ route('register') }}">Register</a></p>
		@endif

		<p style="color:#6b7280">Laravel {{ $laravelVersion }} · PHP {{ $phpVersion }}</p>
	</main>

	<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
