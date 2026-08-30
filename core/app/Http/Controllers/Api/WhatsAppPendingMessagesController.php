<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Demand;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WhatsAppPendingMessagesController extends Controller
{
    /**
     * Returns finalization messages ready to be typed and sent by the
     * extension, then immediately marks them as delivered so they are not
     * returned again on the next poll.
     */
    public function index(): JsonResponse
    {
        return DB::transaction(function () {
            $demands = Demand::with('contact')
                ->pendingWhatsappDelivery()
                ->lockForUpdate()
                ->get();

            $payload = $demands->map(fn (Demand $demand) => [
                'demand_id' => $demand->id,
                'contact' => $demand->contact->phone_number,
                'message' => $demand->final_message,
            ])->values();

            Demand::whereIn('id', $demands->pluck('id'))->update(['whatsapp_sent_at' => now()]);

            return response()->json(['pending' => $payload]);
        });
    }
}
