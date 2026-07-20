<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Http\Request;

class ChatMessagesController extends Controller
{
    public function index(Request $request, ChatSession $session)
    {
        $user = $request->user();
        $selectedDevice = session()->get('selectedDevice');
        $deviceBody = is_array($selectedDevice) ? ($selectedDevice['device_body'] ?? null) : null;

        if ((int) $session->user_id !== (int) $user->id) {
            abort(404);
        }

        if ($deviceBody && (string) $session->body !== (string) $deviceBody) {
            abort(404);
        }

        $messages = ChatMessage::query()
            ->where('session_id', $session->id)
            ->orderBy('id')
            ->limit(300)
            ->get()
            ->map(function (ChatMessage $message) use ($session) {
                return [
                    'id' => $message->id,
                    'session_id' => $message->session_id,
                    'wapp_id' => $message->wapp_id,
                    'number' => $message->number,
                    'phone_number' => $session->phone_number,
                    'direction' => $message->direction ?: 'incoming',
                    'type' => $message->type ?: 'text',
                    'message' => (string) ($message->message ?? ''),
                    'push_name' => (string) ($message->push_name ?? ''),
                    'attachment' => (string) ($message->attachment ?? ''),
                    'original_file' => (string) ($message->original_file ?? ''),
                    'created_at' => optional($message->created_at)->toIso8601String(),
                    'updated_at' => optional($message->updated_at)->toIso8601String(),
                ];
            })
            ->values();

        return response()->json($messages);
    }
}
