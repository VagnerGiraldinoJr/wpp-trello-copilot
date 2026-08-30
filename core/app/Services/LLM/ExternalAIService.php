<?php

namespace App\Services\LLM;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fallback AI engine using a hosted API (OpenAI or Anthropic) instead of a
 * local Ollama instance. The provider and API key are configured on the
 * setup panel (Aba 2 - Motor de IA).
 */
class ExternalAIService implements LLMServiceInterface
{
    use ParsesDemandResponse;

    public function __construct(
        private readonly ?string $provider = null,
        private readonly ?string $apiKey = null,
        private readonly ?string $model = null,
    ) {}

    public function analyzeDemand(string $messages): array
    {
        $provider = $this->provider ?? AppSetting::get('external_ai_provider', 'openai');
        $apiKey = $this->apiKey ?? AppSetting::get('external_ai_api_key');
        $model = $this->model ?? AppSetting::get('external_ai_model');

        if (empty($apiKey)) {
            Log::warning('ExternalAIService: missing API key');

            return $this->fallback();
        }

        try {
            return match ($provider) {
                'claude' => $this->analyzeWithClaude($messages, $apiKey, $model ?: 'claude-3-5-sonnet-latest'),
                default => $this->analyzeWithOpenAi($messages, $apiKey, $model ?: 'gpt-4o-mini'),
            };
        } catch (Throwable $e) {
            Log::error('ExternalAIService analyzeDemand error: '.$e->getMessage());

            return $this->fallback();
        }
    }

    private function analyzeWithOpenAi(string $messages, string $apiKey, string $model): array
    {
        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'user', 'content' => $this->promptFor($messages)],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('OpenAI request failed', ['status' => $response->status()]);

            return $this->fallback();
        }

        $content = (string) ($response->json('choices.0.message.content') ?? '');

        return $this->parseJsonResponse($content);
    }

    private function analyzeWithClaude(string $messages, string $apiKey, string $model): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])
            ->timeout(60)
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 1024,
                'messages' => [
                    ['role' => 'user', 'content' => $this->promptFor($messages)],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('Claude request failed', ['status' => $response->status()]);

            return $this->fallback();
        }

        $content = (string) ($response->json('content.0.text') ?? '');

        return $this->parseJsonResponse($content);
    }
}
