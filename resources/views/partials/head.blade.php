<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#1d4851">

<title>
    {{ filled($title ?? null) ? $title.' — '.config('marche.name') : config('marche.name') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/images/fleur.png" type="image/png">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
