<?php

namespace App\Livewire\Setup;

use App\Models\AppSetting;
use App\Services\Trello\TrelloService;
use Livewire\Component;
use Throwable;

class TrelloConnection extends Component
{
    public string $apiKey = '';
    public string $token = '';

    /** @var array<int, array{id: string, name: string}> */
    public array $boards = [];

    /** @var array<int, array{id: string, name: string}> */
    public array $lists = [];

    public ?string $boardId = null;
    public ?string $inboxListId = null;
    public ?string $resolvedListId = null;

    public ?string $statusMessage = null;
    public bool $statusIsError = false;

    public function mount(): void
    {
        $this->apiKey = (string) AppSetting::get('trello_api_key', '');
        $this->token = (string) AppSetting::get('trello_token', '');
        $this->boardId = AppSetting::get('trello_board_id');
        $this->inboxListId = AppSetting::get('trello_list_inbox_id');
        $this->resolvedListId = AppSetting::get('trello_list_resolved_id');

        if ($this->apiKey && $this->token) {
            $this->loadBoards(silently: true);
        }

        if ($this->boardId) {
            $this->loadLists(silently: true);
        }
    }

    public function connect(): void
    {
        $this->validate([
            'apiKey' => ['required', 'string'],
            'token' => ['required', 'string'],
        ]);

        AppSetting::set('trello_api_key', $this->apiKey);
        AppSetting::set('trello_token', $this->token);

        $this->loadBoards();
    }

    public function updatedBoardId(): void
    {
        $this->lists = [];
        $this->inboxListId = null;
        $this->resolvedListId = null;

        if ($this->boardId) {
            AppSetting::set('trello_board_id', $this->boardId);
            $this->loadLists();
        }
    }

    public function saveLists(): void
    {
        $this->validate([
            'inboxListId' => ['required', 'string'],
            'resolvedListId' => ['required', 'string', 'different:inboxListId'],
        ]);

        AppSetting::set('trello_list_inbox_id', $this->inboxListId);
        AppSetting::set('trello_list_resolved_id', $this->resolvedListId);

        try {
            app(TrelloService::class)->registerWebhook(
                url('/api/trello/webhook'),
                $this->boardId,
            );

            $this->statusIsError = false;
            $this->statusMessage = 'Configuração salva e webhook registrado no Trello.';
        } catch (Throwable $e) {
            $this->statusIsError = false;
            $this->statusMessage = 'Configuração salva. Não foi possível confirmar o webhook automaticamente ('.$e->getMessage().'), mas a sincronização de cards continuará funcionando.';
        }
    }

    private function loadBoards(bool $silently = false): void
    {
        try {
            $this->boards = app(TrelloService::class, ['apiKey' => $this->apiKey, 'token' => $this->token])->getBoards();
            $this->statusIsError = false;
            $this->statusMessage = $silently ? null : 'Conectado ao Trello com sucesso.';
        } catch (Throwable $e) {
            $this->boards = [];
            $this->statusIsError = true;
            $this->statusMessage = 'Falha ao conectar ao Trello: '.$e->getMessage();
        }
    }

    private function loadLists(bool $silently = false): void
    {
        try {
            $this->lists = app(TrelloService::class)->getLists($this->boardId);

            if (! $silently) {
                $this->statusIsError = false;
                $this->statusMessage = null;
            }
        } catch (Throwable $e) {
            $this->lists = [];
            $this->statusIsError = true;
            $this->statusMessage = 'Falha ao carregar colunas do quadro: '.$e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.setup.trello-connection');
    }
}
