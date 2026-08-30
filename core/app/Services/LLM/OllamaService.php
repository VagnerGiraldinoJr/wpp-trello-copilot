<?php

namespace App\Services\LLM;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OllamaService implements LLMServiceInterface
{
    use ParsesDemandResponse;

    public function __construct(
        private readonly ?string $baseUrl = null,
        private readonly ?string $model = null,
    ) {}

    public function analyzeDemand(string $messages): array
    {
        $baseUrl = $this->baseUrl ?? AppSetting::get('ollama_url', config('services.ollama.url'));
        $model = $this->model ?? AppSetting::get('ollama_model', config('services.ollama.model'));

        try {
            $response = Http::timeout(60)
                ->post(rtrim($baseUrl, '/').'/api/generate', [
                    'model' => $model,
                    'prompt' => $this->promptFor($messages),
                    'stream' => false,
                    'format' => 'json',
                ]);

            if (! $response->successful()) {
                Log::warning('Ollama request failed', ['status' => $response->status()]);

                return $this->fallback();
            }

            $body = (string) ($response->json('response') ?? '');

            return $this->parseJsonResponse($body);
        } catch (Throwable $e) {
            Log::error('Ollama analyzeDemand error: '.$e->getMessage());

            return $this->fallback();
        }
    }
}
