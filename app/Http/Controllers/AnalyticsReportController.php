<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Device;
use App\Models\MessageHistory;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $from = $this->parseDate($request->input('from'), now()->subDays(6))->startOfDay();
        $to = $this->parseDate($request->input('to'), now())->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $baseQuery = MessageHistory::query()
            ->with('device')
            ->whereBetween('message_histories.created_at', [$from, $to])
            ->when($user->level !== 'admin', fn ($query) => $query->where('message_histories.user_id', $user->id));

        $this->applyFilters($baseQuery, $request, $user);

        $total = (clone $baseQuery)->count();
        $success = (clone $baseQuery)->where('status', 'success')->count();
        $failed = (clone $baseQuery)->where('status', 'failed')->count();
        $api = (clone $baseQuery)->where('send_by', 'api')->count();
        $web = (clone $baseQuery)->where('send_by', 'web')->count();
        $uniqueRecipients = (clone $baseQuery)->distinct('number')->count('number');
        $activeDevices = (clone $baseQuery)->distinct('device_id')->count('device_id');
        $incomingReplies = $this->incomingReplyCount($user, $from, $to);

        $summary = [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'api' => $api,
            'web' => $web,
            'unique_recipients' => $uniqueRecipients,
            'active_devices' => $activeDevices,
            'incoming_replies' => $incomingReplies,
            'delivery_rate' => $total > 0 ? round(($success / $total) * 100, 1) : 0,
            'failure_rate' => $total > 0 ? round(($failed / $total) * 100, 1) : 0,
            'read_count' => null,
        ];

        $dailyRows = (clone $baseQuery)
            ->selectRaw('DATE(message_histories.created_at) as date')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success")
            ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->groupBy(DB::raw('DATE(message_histories.created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $daily = collect(CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay()))
            ->map(function (Carbon $date) use ($dailyRows) {
                $key = $date->toDateString();
                $row = $dailyRows->get($key);

                return [
                    'date' => $date->format('d M'),
                    'total' => (int) ($row->total ?? 0),
                    'success' => (int) ($row->success ?? 0),
                    'failed' => (int) ($row->failed ?? 0),
                ];
            })
            ->values();

        $typeBreakdown = (clone $baseQuery)
            ->selectRaw("COALESCE(NULLIF(type, ''), 'unknown') as label")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $deviceBreakdown = (clone $baseQuery)
            ->leftJoin('devices', 'devices.id', '=', 'message_histories.device_id')
            ->selectRaw("COALESCE(devices.body, CONCAT('Device #', message_histories.device_id)) as device_name")
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN message_histories.status = 'success' THEN 1 ELSE 0 END) as success")
            ->selectRaw("SUM(CASE WHEN message_histories.status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->groupBy('message_histories.device_id', 'devices.body')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $recentFailures = (clone $baseQuery)
            ->where('status', 'failed')
            ->latest('message_histories.created_at')
            ->limit(8)
            ->get();

        $devices = Device::query()
            ->when($user->level !== 'admin', fn ($query) => $query->where('user_id', $user->id))
            ->orderBy('body')
            ->get(['id', 'body']);

        return view('theme::pages.analytics.index', compact(
            'summary',
            'daily',
            'typeBreakdown',
            'deviceBreakdown',
            'recentFailures',
            'devices',
            'from',
            'to'
        ));
    }

    private function parseDate(?string $value, $fallback): Carbon
    {
        try {
            return $value ? Carbon::parse($value) : Carbon::parse($fallback);
        } catch (\Throwable $exception) {
            return Carbon::parse($fallback);
        }
    }

    private function applyFilters($query, Request $request, $user): void
    {
        if ($request->filled('device_id')) {
            $query->where('message_histories.device_id', $request->input('device_id'));
        }

        if (in_array($request->input('status'), ['success', 'failed'], true)) {
            $query->where('message_histories.status', $request->input('status'));
        }

        if (in_array($request->input('send_by'), ['api', 'web'], true)) {
            $query->where('message_histories.send_by', $request->input('send_by'));
        }

        if ($user->level !== 'admin' && $request->filled('device_id')) {
            $query->whereExists(function ($exists) use ($user) {
                $exists->select(DB::raw(1))
                    ->from('devices')
                    ->whereColumn('devices.id', 'message_histories.device_id')
                    ->where('devices.user_id', $user->id);
            });
        }
    }

    private function incomingReplyCount($user, Carbon $from, Carbon $to): int
    {
        return ChatMessage::query()
            ->join('chat_sessions', 'chat_sessions.id', '=', 'chat_messages.session_id')
            ->where('chat_messages.direction', 'incoming')
            ->whereBetween('chat_messages.created_at', [$from, $to])
            ->when($user->level !== 'admin', fn ($query) => $query->where('chat_sessions.user_id', $user->id))
            ->count();
    }
}
