<?php

namespace App\Livewire\Setup;

use App\Models\AppSetting;
use Livewire\Component;

class AiEngine extends Component
{
    public string $engine = 'ollama';

    public string $ollamaUrl = '';
    public string $ollamaModel = '';

    public string $externalProvider = 'openai';
    public string $externalApiKey = '';
    public string $externalModel = '';

    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->engine = (string) AppSetting::get('ai_engine', 'ollama');
        $this->ollamaUrl = (string) AppSetting::get('ollama_url', config('services.ollama.url'));
        $this->ollamaModel = (string) AppSetting::get('ollama_model', config('services.ollama.model'));
        $this->externalProvider = (string) AppSetting::get('external_ai_provider', 'openai');
        $this->externalApiKey = (string) AppSetting::get('external_ai_api_key', '');
        $this->externalModel = (string) AppSetting::get('external_ai_model', '');
    }

    public function save(): void
    {
        $this->validate([
            'engine' => ['required', 'in:ollama,external'],
            'ollamaUrl' => ['required_if:engine,ollama', 'nullable', 'url'],
            'ollamaModel' => ['required_if:engine,ollama', 'nullable', 'string'],
            'externalProvider' => ['required_if:engine,external', 'nullable', 'in:openai,claude'],
            'externalApiKey' => ['required_if:engine,external', 'nullable', 'string'],
            'externalModel' => ['nullable', 'string'],
        ]);

        AppSetting::set('ai_engine', $this->engine);
        AppSetting::set('ollama_url', $this->ollamaUrl);
        AppSetting::set('ollama_model', $this->ollamaModel);
        AppSetting::set('external_ai_provider', $this->externalProvider);
        AppSetting::set('external_ai_api_key', $this->externalApiKey);
        AppSetting::set('external_ai_model', $this->externalModel);

        $this->statusMessage = 'Configurações de IA salvas com sucesso.';
    }

    public function render()
    {
        return view('livewire.setup.ai-engine');
    }
}
