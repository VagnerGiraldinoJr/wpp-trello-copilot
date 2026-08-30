<div class="space-y-6 rounded-xl border border-slate-800 bg-slate-900/50 p-6">
    <div>
        <h2 class="text-base font-semibold text-white">Template de Notificação</h2>
        <p class="mt-1 text-sm text-slate-400">
            Mensagem enviada automaticamente ao cliente quando o card é movido para a coluna "Resolvido".
            Use as tags <code class="rounded bg-slate-800 px-1">{nome}</code> e <code class="rounded bg-slate-800 px-1">{titulo}</code>.
        </p>
    </div>

    @if ($statusMessage)
        <div class="rounded-lg bg-emerald-950 px-4 py-2 text-sm text-emerald-300">{{ $statusMessage }}</div>
    @endif

    <form wire:submit="save" class="space-y-4">
        <div>
            <label class="mb-1 block text-sm font-medium text-slate-300">Mensagem</label>
            <textarea wire:model.live="template" rows="4"
                      class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white focus:border-emerald-500 focus:outline-none"></textarea>
            @error('template') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <span class="mb-1 block text-sm font-medium text-slate-300">Pré-visualização</span>
            <div class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-sm text-slate-300">
                {{ $this->preview }}
            </div>
        </div>

        <button type="submit"
                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">
            Salvar template
        </button>
    </form>
</div>
