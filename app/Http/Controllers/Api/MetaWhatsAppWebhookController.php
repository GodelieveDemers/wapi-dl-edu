<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetaWhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        $validTokens = DB::table('whatsapp_cloud_channels')
            ->whereNotNull('verify_token')
            ->pluck('verify_token')
            ->filter()
            ->values()
            ->all();

        $envToken = env('META_WA_WEBHOOK_VERIFY_TOKEN');
        if ($envToken) {
            $validTokens[] = $envToken;
        }

        if ($mode === 'subscribe' && $challenge && in_array($token, $validTokens, true)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request)
    {
        $payload = $request->all();
        $phoneNumberId = data_get($payload, 'entry.0.changes.0.value.metadata.phone_number_id');
        $channel = $phoneNumberId
            ? DB::table('whatsapp_cloud_channels')->where('phone_number_id', $phoneNumberId)->first()
            : null;

        DB::table('whatsapp_cloud_webhook_logs')->insert([
            'user_id' => $channel->user_id ?? null,
            'phone_number_id' => $phoneNumberId,
            'event_type' => data_get($payload, 'entry.0.changes.0.field', 'messages'),
            'payload' => json_encode($payload),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['status' => true]);
    }
}
