<div class="space-y-6 rounded-xl border border-slate-800 bg-slate-900/50 p-6">
    <div>
        <h2 class="text-base font-semibold text-white">Conexão com o Trello</h2>
        <p class="mt-1 text-sm text-slate-400">
            Gere sua API Key e Token em
            <span class="font-mono text-slate-300">https://trello.com/power-ups/admin</span>.
        </p>
    </div>

    @if ($statusMessage)
        <div class="rounded-lg px-4 py-2 text-sm {{ $statusIsError ? 'bg-red-950 text-red-300' : 'bg-emerald-950 text-emerald-300' }}">
            {{ $statusMessage }}
        </div>
    @endif

    <form wire:submit="connect" class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-300">API Key</label>
            <input type="password" wire:model="apiKey"
                   class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
            @error('apiKey') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-300">Token</label>
            <input type="password" wire:model="token"
                   class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
            @error('token') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <button type="submit"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">
                Conectar
            </button>
        </div>
    </form>

    @if (!empty($boards))
        <div class="grid gap-4 border-t border-slate-800 pt-6 sm:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-300">Quadro</label>
                <select wire:model.live="boardId"
                        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
                    <option value="">Selecione...</option>
                    @foreach ($boards as $board)
                        <option value="{{ $board['id'] }}">{{ $board['name'] }}</option>
                    @endforeach
                </select>
            </div>

            @if (!empty($lists))
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-300">Coluna de Entrada</label>
                    <select wire:model="inboxListId"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
                        <option value="">Selecione...</option>
                        @foreach ($lists as $list)
                            <option value="{{ $list['id'] }}">{{ $list['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-300">Coluna de Resolvido</label>
                    <select wire:model="resolvedListId"
                            class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none">
                        <option value="">Selecione...</option>
                        @foreach ($lists as $list)
                            <option value="{{ $list['id'] }}">{{ $list['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-3">
                    <button type="button" wire:click="saveLists"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">
                        Salvar colunas e registrar webhook
                    </button>
                    @error('inboxListId') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                    @error('resolvedListId') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
            @endif
        </div>
    @endif
</div>
