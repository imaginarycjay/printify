<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="theme-color" content="#f1f5f9" media="(prefers-color-scheme: light)" />
<meta name="theme-color" content="#0b1120" media="(prefers-color-scheme: dark)" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'PressQueue') : config('app.name', 'PressQueue') }}
</title>

<link rel="icon" href="/favicon.ico?v=2" sizes="any">
<link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
