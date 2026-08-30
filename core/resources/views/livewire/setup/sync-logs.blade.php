<div wire:poll.10s class="space-y-4 rounded-xl border border-slate-800 bg-slate-900/50 p-6">
    <div>
        <h2 class="text-base font-semibold text-white">Logs de Sincronização &amp; Demandas</h2>
        <p class="mt-1 text-sm text-slate-400">Demandas detectadas pela IA a partir das conversas sincronizadas.</p>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-800">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-950 text-xs uppercase text-slate-400">
                <tr>
                    <th class="px-4 py-2">Contato</th>
                    <th class="px-4 py-2">Demanda</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Card Trello</th>
                    <th class="px-4 py-2">Criado em</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse ($demands as $demand)
                    <tr>
                        <td class="px-4 py-3 text-slate-200">{{ $demand->contact->name }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ $demand->title }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-full px-2 py-1 text-xs font-medium',
                                'bg-amber-950 text-amber-300' => $demand->status === 'created',
                                'bg-emerald-950 text-emerald-300' => $demand->status === 'resolved',
                                'bg-slate-800 text-slate-400' => $demand->status === 'ignored',
                            ])>
                                {{ $demand->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-400">{{ $demand->trello_card_id ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $demand->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-slate-500">Nenhuma demanda registrada ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $demands->links() }}
</div>
