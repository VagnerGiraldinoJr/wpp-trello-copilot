<?php

namespace App\Livewire\Setup;

use App\Models\AppSetting;
use Livewire\Component;

class NotificationTemplates extends Component
{
    private const DEFAULT_TEMPLATE = 'Olá {nome}, sua solicitação "{titulo}" foi concluída! ✅';

    public string $template = '';

    public ?string $statusMessage = null;

    public function mount(): void
    {
        $this->template = (string) AppSetting::get('notification_template', self::DEFAULT_TEMPLATE);
    }

    public function save(): void
    {
        $this->validate([
            'template' => ['required', 'string', 'max:1000'],
        ]);

        AppSetting::set('notification_template', $this->template);

        $this->statusMessage = 'Template salvo com sucesso.';
    }

    public function getPreviewProperty(): string
    {
        return strtr($this->template, [
            '{nome}' => 'Maria',
            '{titulo}' => 'Ajuste no pedido #123',
        ]);
    }

    public function render()
    {
        return view('livewire.setup.notification-templates');
    }
}
