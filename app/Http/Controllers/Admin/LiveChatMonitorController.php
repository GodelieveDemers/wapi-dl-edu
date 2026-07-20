<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LiveChatMonitorController extends Controller
{
    public function index(Request $request): View
    {
        return view('theme::pages.admin.live-chat-monitor', [
            'devices' => $this->deviceOptions(),
            'stats' => $this->summaryStats($request),
            'socketUrl' => env('WA_URL_SERVER', url('/')),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->integer('limit', 100), 25), 300);
        $query = $this->baseQuery($request);

        $rows = $query
            ->orderByDesc('chat_messages.created_at')
            ->orderByDesc('chat_messages.id')
            ->limit($limit)
            ->get()
            ->map(fn ($row): array => [
                'id' => (int) $row->id,
                'session_id' => (int) $row->session_id,
                'wapp_id' => $row->wapp_id,
                'created_at' => $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d H:i:s') : null,
                'device' => $row->device,
                'contact_name' => $row->contact_name ?: '-',
                'phone_number' => $row->phone_number,
                'direction' => $row->direction,
                'type' => $row->type ?: 'text',
                'message' => $this->shortenMessage((string) $row->message),
                'attachment' => $row->attachment,
                'owner_name' => $row->owner_name ?: '-',
                'owner_email' => $row->owner_email ?: '-',
            ]);

        return response()->json([
            'ok' => true,
            'server_time' => now()->format('Y-m-d H:i:s'),
            'stats' => $this->summaryStats($request),
            'rows' => $rows,
        ]);
    }

    private function baseQuery(Request $request)
    {
        $query = DB::table('chat_messages')
            ->join('chat_sessions', 'chat_sessions.id', '=', 'chat_messages.session_id')
            ->leftJoin('users', 'users.id', '=', 'chat_sessions.user_id')
            ->select([
                'chat_messages.id',
                'chat_messages.session_id',
                'chat_messages.wapp_id',
                'chat_messages.direction',
                'chat_messages.message',
                'chat_messages.type',
                'chat_messages.attachment',
                'chat_messages.created_at',
                'chat_sessions.body as device',
                'chat_sessions.phone_number',
                'chat_sessions.push_name as contact_name',
                'users.username as owner_name',
                'users.email as owner_email',
            ]);

        if (auth()->user()?->level !== 'admin') {
            $query->where('chat_sessions.user_id', auth()->id());
        }

        if ($request->filled('device')) {
            $query->where('chat_sessions.body', (string) $request->input('device'));
        }

        if (in_array($request->input('direction'), ['incoming', 'outgoing'], true)) {
            $query->where('chat_messages.direction', $request->input('direction'));
        }

        if ($request->filled('date_from')) {
            $query->where('chat_messages.created_at', '>=', Carbon::parse($request->input('date_from'))->startOfDay());
        }

        if ($request->filled('date_to')) {
            $query->where('chat_messages.created_at', '<=', Carbon::parse($request->input('date_to'))->endOfDay());
        }

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));
            $query->where(function ($query) use ($keyword): void {
                $query->where('chat_messages.message', 'like', "%{$keyword}%")
                    ->orWhere('chat_sessions.push_name', 'like', "%{$keyword}%")
                    ->orWhere('chat_sessions.phone_number', 'like', "%{$keyword}%")
                    ->orWhere('chat_sessions.body', 'like', "%{$keyword}%")
                    ->orWhere('users.username', 'like', "%{$keyword}%")
                    ->orWhere('users.email', 'like', "%{$keyword}%");
            });
        }

        return $query;
    }

    private function summaryStats(Request $request): array
    {
        $query = $this->baseQuery($request);

        return [
            'total' => (clone $query)->count(),
            'incoming' => (clone $query)->where('chat_messages.direction', 'incoming')->count(),
            'outgoing' => (clone $query)->where('chat_messages.direction', 'outgoing')->count(),
            'today' => (clone $query)->whereDate('chat_messages.created_at', today())->count(),
        ];
    }

    private function deviceOptions()
    {
        $query = DB::table('chat_sessions')
            ->select('body')
            ->whereNotNull('body')
            ->where('body', '!=', '');

        if (auth()->user()?->level !== 'admin') {
            $query->where('user_id', auth()->id());
        }

        return $query
            ->distinct()
            ->orderBy('body')
            ->pluck('body');
    }

    private function shortenMessage(string $message): string
    {
        $message = trim(preg_replace('/\s+/', ' ', $message) ?: '');

        return mb_strlen($message) > 240 ? mb_substr($message, 0, 240) . '...' : $message;
    }
}
