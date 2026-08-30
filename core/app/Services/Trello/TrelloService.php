<?php

namespace App\Services\Trello;

use App\Models\AppSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class TrelloService
{
    private const BASE_URL = 'https://api.trello.com/1';

    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly ?string $token = null,
    ) {}

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function getBoards(): array
    {
        $response = $this->client()
            ->get(self::BASE_URL.'/members/me/boards', [
                'fields' => 'name',
                'filter' => 'open',
            ])
            ->throw();

        return collect($response->json())
            ->map(fn (array $board) => ['id' => $board['id'], 'name' => $board['name']])
            ->all();
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function getLists(string $boardId): array
    {
        $response = $this->client()
            ->get(self::BASE_URL."/boards/{$boardId}/lists", [
                'fields' => 'name',
                'filter' => 'open',
            ])
            ->throw();

        return collect($response->json())
            ->map(fn (array $list) => ['id' => $list['id'], 'name' => $list['name']])
            ->all();
    }

    public function createCard(string $listId, string $name, string $description = ''): array
    {
        $response = $this->client()
            ->post(self::BASE_URL.'/cards', [
                'idList' => $listId,
                'name' => $name,
                'desc' => $description,
            ])
            ->throw();

        return $response->json();
    }

    public function deleteCard(string $cardId): void
    {
        $this->client()
            ->delete(self::BASE_URL."/cards/{$cardId}")
            ->throw();
    }

    public function renameCard(string $cardId, string $name): array
    {
        $response = $this->client()
            ->put(self::BASE_URL."/cards/{$cardId}", [
                'name' => $name,
            ])
            ->throw();

        return $response->json();
    }

    public function registerWebhook(string $callbackUrl, string $idModel, string $description = 'wpp-trello-copilot'): array
    {
        $response = $this->client()
            ->post(self::BASE_URL.'/webhooks', [
                'callbackURL' => $callbackUrl,
                'idModel' => $idModel,
                'description' => $description,
            ])
            ->throw();

        return $response->json();
    }

    /**
     * @throws RequestException
     */
    private function client(): PendingRequest
    {
        $apiKey = $this->apiKey ?? AppSetting::get('trello_api_key');
        $token = $this->token ?? AppSetting::get('trello_token');

        return Http::asJson()
            ->timeout(30)
            ->withQueryParameters([
                'key' => $apiKey,
                'token' => $token,
            ]);
    }
}
