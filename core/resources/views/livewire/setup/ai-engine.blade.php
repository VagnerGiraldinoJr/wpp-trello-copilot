<div x-data="{ engine: @entangle('engine') }" class="space-y-6 rounded-xl border border-slate-800 bg-slate-900/50 p-6">
    <div>
        <h2 class="text-base font-semibold text-white">Motor de IA</h2>
        <p class="mt-1 text-sm text-slate-400">
            Use um modelo local via Ollama (100% privado) ou uma API externa (OpenAI/Claude) como fallback.
        </p>
    </div>

    @if ($statusMessage)
        <div class="rounded-lg bg-emerald-950 px-4 py-2 text-sm text-emerald-300">{{ $statusMessage }}</div>
    @endif

    <div class="flex gap-3">
        <button type="button" @click="engine = 'ollama'"
                :class="engine === 'ollama' ? 'border-emerald-500 bg-emerald-950' : 'border-slate-700 bg-slate-950'"
                class="flex-1 rounded-lg border px-4 py-3 text-left transition">
            <span class="block text-sm font-medium text-white">🖥️ Ollama Local</span>
            <span class="block text-xs text-slate-400">Roda no seu computador, nada sai da máquina.</span>
        </button>
        <button type="button" @click="engine = 'external'"
                :class="engine === 'external' ? 'border-emerald-500 bg-emerald-950' : 'border-slate-700 bg-slate-950'"
                class="flex-1 rounded-lg border px-4 py-3 text-left transition">
            <span class="block text-sm font-medium text-white">☁️ API Externa</span>
            <span class="block text-xs text-slate-400">OpenAI ou Claude, via API Key.</span>
        </button>
    </div>

    <form wire:submit="save" class="space-y-4">
        <div x-show="engine === 'ollama'" class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-300">URL do Ollama</label>
                <input type="text" wire:model="ollamaUrl" placeholder="http://localhost:11434"
                       class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
                @error('ollamaUrl') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-300">Modelo</label>
                <input type="text" wire:model="ollamaModel" placeholder="llama3.1"
                       class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
                @error('ollamaModel') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <div x-show="engine === 'external'" class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-300">Provedor</label>
                <select wire:model="externalProvider"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
                    <option value="openai">OpenAI</option>
                    <option value="claude">Claude (Anthropic)</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-300">Modelo</label>
                <input type="text" wire:model="externalModel" placeholder="gpt-4o-mini / claude-3-5-sonnet-latest"
                       class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-medium text-slate-300">API Key</label>
                <input type="password" wire:model="externalApiKey"
                       class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
                @error('externalApiKey') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <button type="submit"
                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">
            Salvar
        </button>
    </form>
</div>
