<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\PrepareWhatsAppCompletionMessageJob;
use App\Models\AppSetting;
use App\Models\Demand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrelloWebhookController extends Controller
{
    /**
     * Trello sends a HEAD (and sometimes GET) request to this URL when the
     * webhook is first registered, just to confirm it is reachable.
     */
    public function verify(): Response
    {
        return response('', 200);
    }

    public function store(Request $request): JsonResponse
    {
        $action = $request->input('action', []);

        if (($action['type'] ?? null) !== 'updateCard') {
            return response()->json(['status' => 'ignored']);
        }

        $card = $action['data']['card'] ?? [];
        $listAfter = $action['data']['listAfter'] ?? null;

        if (! $listAfter) {
            return response()->json(['status' => 'ignored']);
        }

        $resolvedListId = AppSetting::get('trello_list_resolved_id');

        if (empty($card['id']) || $listAfter['id'] !== $resolvedListId) {
            return response()->json(['status' => 'ignored']);
        }

        $demand = Demand::where('trello_card_id', $card['id'])->first();

        if (! $demand) {
            return response()->json(['status' => 'demand_not_found']);
        }

        $demand->update(['status' => Demand::STATUS_RESOLVED]);

        PrepareWhatsAppCompletionMessageJob::dispatch($demand->id);

        return response()->json(['status' => 'resolved']);
    }
}
