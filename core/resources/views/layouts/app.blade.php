<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} &middot; Setup</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <div class="min-h-screen">
        <header class="border-b border-slate-800 bg-slate-900/60">
            <div class="mx-auto flex max-w-5xl items-center gap-3 px-6 py-4">
                <span class="text-2xl">🤝</span>
                <div>
                    <h1 class="text-lg font-semibold text-white">WPP Trello Copilot</h1>
                    <p class="text-xs text-slate-400">Sincronização local WhatsApp → Trello, com IA privacy-first</p>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-6 py-8">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
