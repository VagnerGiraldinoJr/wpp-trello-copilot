<?php

namespace App\Services\LLM;

use App\Models\AppSetting;

class LLMServiceFactory
{
    public static function make(): LLMServiceInterface
    {
        $engine = AppSetting::get('ai_engine', 'ollama');

        return $engine === 'external'
            ? app(ExternalAIService::class)
            : app(OllamaService::class);
    }
}
