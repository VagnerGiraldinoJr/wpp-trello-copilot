<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncWhatsAppMessagesRequest;
use App\Jobs\ProcessWhatsAppDemandJob;
use App\Models\Contact;
use App\Models\ConversationMessage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class WhatsAppSyncController extends Controller
{
    public function store(SyncWhatsAppMessagesRequest $request): JsonResponse
    {
        $data = $request->validated();

        $contact = Contact::firstOrCreate(
            ['phone_number' => $data['contact']],
            ['name' => $data['contact']],
        );

        $contact->update(['last_synced_at' => $this->parseTimestamp($data['synced_at']) ?? now()]);

        $storedCount = 0;

        foreach ($data['messages'] as $message) {
            $sentAt = $this->parseTimestamp($message['sent_at'] ?? null);

            $created = ConversationMessage::firstOrCreate(
                ['message_hash' => ConversationMessage::hashFor($contact->id, $message['body'], $message['sent_at'] ?? null)],
                [
                    'contact_id' => $contact->id,
                    'body' => $message['body'],
                    'sent_at' => $sentAt,
                ],
            );

            if ($created->wasRecentlyCreated) {
                $storedCount++;
            }
        }

        if ($storedCount > 0) {
            ProcessWhatsAppDemandJob::dispatch($contact->id);
        }

        return response()->json([
            'contact_id' => $contact->id,
            'messages_received' => count($data['messages']),
            'messages_stored' => $storedCount,
        ], 201);
    }

    private function parseTimestamp(mixed $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            $value = (int) $value;

            return Carbon::createFromTimestamp($value > 1e12 ? intdiv($value, 1000) : $value);
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
