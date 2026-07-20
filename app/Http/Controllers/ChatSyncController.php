<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ChatSyncController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $selectedDevice = session()->get('selectedDevice');
        $deviceBody = is_array($selectedDevice) ? ($selectedDevice['device_body'] ?? null) : null;
        $activeSessionId = $request->input('active_session_id');
        $lastMessageId = (int) $request->input('last_message_id', 0);

        $sessionsQuery = ChatSession::query()
            ->where('user_id', $user->id)
            ->when($deviceBody, fn ($query) => $query->where('body', $deviceBody))
            ->withCount([
                'messages as unread_count' => function ($query) {
                    $query->where('direction', 'incoming')
                        ->where(function ($query) {
                            $query->whereColumn('chat_messages.created_at', '>', 'chat_sessions.last_seen_at')
                                ->orWhereNull('chat_sessions.last_seen_at');
                        });
                },
            ])
            ->orderByDesc('updated_at')
            ->limit(100);

        $sessions = $sessionsQuery->get()->map(function (ChatSession $session) {
            return [
                'id' => $session->id,
                'body' => $session->body,
                'phone_number' => $session->phone_number,
                'push_name' => $session->push_name,
                'cs_name' => $session->cs_name,
                'profile_sender' => $session->profile_sender,
                'profile_receive' => $session->profile_receive,
                'stop_ai' => (int) $session->stop_ai,
                'last_message' => $session->last_message,
                'last_seen_at' => $session->last_seen_at ? Carbon::parse($session->last_seen_at)->toDateTimeString() : null,
                'updated_at' => optional($session->updated_at)->toIso8601String(),
                'unread_count' => (int) $session->unread_count,
            ];
        });

        $messages = [];
        if ($activeSessionId) {
            $activeSession = ChatSession::query()
                ->where('user_id', $user->id)
                ->when($deviceBody, fn ($query) => $query->where('body', $deviceBody))
                ->find($activeSessionId);

            if ($activeSession) {
                $activeSession->forceFill(['last_seen_at' => Carbon::now()])->save();

                if ($lastMessageId > 0) {
                    $messages = ChatMessage::query()
                        ->where('session_id', $activeSession->id)
                        ->where('id', '>', $lastMessageId)
                        ->orderBy('id')
                        ->limit(100)
                        ->get();
                }
            }
        }

        return response()->json([
            'ok' => true,
            'server_time' => Carbon::now()->toIso8601String(),
            'sessions' => $sessions,
            'messages' => $messages,
        ]);
    }

    public function markRead(Request $request, ChatSession $session)
    {
        $user = $request->user();
        $selectedDevice = session()->get('selectedDevice');
        $deviceBody = is_array($selectedDevice) ? ($selectedDevice['device_body'] ?? null) : null;

        if ((int) $session->user_id !== (int) $user->id || ($deviceBody && $session->body !== $deviceBody)) {
            abort(404);
        }

        $session->forceFill(['last_seen_at' => Carbon::now()])->save();

        return response()->json(['ok' => true]);
    }
}
