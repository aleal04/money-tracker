<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>Money Tracker</title>

    <meta name="theme-color" content="#ffffff" />
    <link rel="manifest" href="/manifest.webmanifest" />
    <link rel="icon" type="image/png" href="/icon-192.png" />
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />
    {{-- Sets the theme class before first paint to avoid a light flash (logic mirrored in useTheme.ts). --}}
    <script>
        try {
            var t = localStorage.getItem('theme')
            if (t === 'dark' || (t !== 'light' && matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark')
                document.querySelector('meta[name="theme-color"]').setAttribute('content', '#0f1012')
            }
        } catch (e) {}
    </script>

    @vite(['resources/scss/app.scss', 'resources/js/app.ts'])
    <x-inertia::head />
</head>

<body>
    <x-inertia::app />
</body>

</html>
