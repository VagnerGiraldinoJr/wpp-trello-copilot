<?php

namespace App\Jobs;

use App\Models\AppSetting;
use App\Models\Demand;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PrepareWhatsAppCompletionMessageJob implements ShouldQueue
{
    use Queueable;

    private const DEFAULT_TEMPLATE = 'Olá {nome}, sua solicitação "{titulo}" foi concluída! ✅';

    public function __construct(
        public readonly int $demandId,
    ) {}

    public function handle(): void
    {
        $demand = Demand::with('contact')->find($this->demandId);

        if (! $demand || ! $demand->contact) {
            return;
        }

        $template = AppSetting::get('notification_template', self::DEFAULT_TEMPLATE);

        $message = strtr($template, [
            '{nome}' => $demand->contact->name,
            '{titulo}' => $demand->title,
        ]);

        $demand->update(['final_message' => $message]);
    }
}
