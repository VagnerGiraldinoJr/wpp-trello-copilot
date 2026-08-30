<?php

namespace App\Jobs;

use App\Models\AppSetting;
use App\Models\Contact;
use App\Models\Demand;
use App\Services\LLM\LLMServiceFactory;
use App\Services\Trello\TrelloService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessWhatsAppDemandJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $contactId,
    ) {}

    public function handle(): void
    {
        $contact = Contact::find($this->contactId);

        if (! $contact) {
            return;
        }

        $messages = $contact->messages()
            ->where('is_processed', false)
            ->orderBy('sent_at')
            ->get();

        if ($messages->isEmpty()) {
            return;
        }

        $transcript = $messages
            ->map(fn ($message) => sprintf('[%s] %s', $message->sent_at?->toDateTimeString(), $message->body))
            ->implode("\n");

        try {
            $analysis = LLMServiceFactory::make()->analyzeDemand($transcript);
        } catch (Throwable $e) {
            Log::error('ProcessWhatsAppDemandJob analysis failed: '.$e->getMessage());

            return;
        }

        $messages->toQuery()->update(['is_processed' => true]);

        if (! ($analysis['is_demand'] ?? false)) {
            return;
        }

        $demand = Demand::create([
            'contact_id' => $contact->id,
            'title' => $analysis['title'] ?: "Demanda de {$contact->name}",
            'summary' => $analysis['summary'],
            'status' => Demand::STATUS_CREATED,
        ]);

        $this->createTrelloCard($demand, $contact);
    }

    private function createTrelloCard(Demand $demand, Contact $contact): void
    {
        $inboxListId = AppSetting::get('trello_list_inbox_id');

        if (empty($inboxListId)) {
            Log::warning('Trello inbox list not configured; skipping card creation', ['demand_id' => $demand->id]);

            return;
        }

        try {
            $card = app(TrelloService::class)->createCard(
                $inboxListId,
                "{$contact->name}: {$demand->title}",
                $demand->summary ?? '',
            );

            $demand->update(['trello_card_id' => $card['id'] ?? null]);
        } catch (Throwable $e) {
            Log::error('Failed to create Trello card for demand '.$demand->id.': '.$e->getMessage());
        }
    }
}
