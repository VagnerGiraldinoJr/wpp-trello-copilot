<div x-data="{ tab: 'trello' }">
    <nav class="mb-6 flex flex-wrap gap-2 border-b border-slate-800 pb-2">
        <button type="button" @click="tab = 'trello'"
                :class="tab === 'trello' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                class="rounded-lg px-4 py-2 text-sm font-medium transition">
            🗂️ Trello
        </button>
        <button type="button" @click="tab = 'ai'"
                :class="tab === 'ai' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                class="rounded-lg px-4 py-2 text-sm font-medium transition">
            🤖 Motor de IA
        </button>
        <button type="button" @click="tab = 'templates'"
                :class="tab === 'templates' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                class="rounded-lg px-4 py-2 text-sm font-medium transition">
            💬 Templates
        </button>
        <button type="button" @click="tab = 'logs'"
                :class="tab === 'logs' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                class="rounded-lg px-4 py-2 text-sm font-medium transition">
            📋 Logs
        </button>
    </nav>

    <div x-show="tab === 'trello'">
        <livewire:setup.trello-connection />
    </div>

    <div x-show="tab === 'ai'">
        <livewire:setup.ai-engine />
    </div>

    <div x-show="tab === 'templates'">
        <livewire:setup.notification-templates />
    </div>

    <div x-show="tab === 'logs'">
        <livewire:setup.sync-logs />
    </div>
</div>
