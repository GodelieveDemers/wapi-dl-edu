<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WhatsAppChannelController extends Controller
{
    public function index(Request $request)
    {
        $cloudChannels = DB::table('whatsapp_cloud_channels')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($channel) {
                $channel->masked_token = $channel->access_token ? 'tersimpan terenkripsi' : 'belum diisi';
                return $channel;
            });

        $numbers = $request->user()->devices()->latest()->paginate(10, ['*'], 'devices_page');
        $webDeviceCount = $request->user()->devices()->count();

        $embeddedSignup = [
            'app_id' => env('META_EMBEDDED_SIGNUP_APP_ID', env('META_WA_APP_ID', '750615218079136')),
            'config_id' => env('META_EMBEDDED_SIGNUP_CONFIG_ID'),
            'graph_version' => env('META_WA_API_VERSION', 'v25.0'),
            'callback_url' => route('whatsapp.channels.embedded.callback'),
        ];

        return view('theme::pages.whatsapp-channels.index', compact('cloudChannels', 'numbers', 'webDeviceCount', 'embeddedSignup'));
    }

    public function storeCloud(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'business_name' => ['nullable', 'string', 'max:160'],
            'phone_number' => ['required', 'string', 'max:30'],
            'phone_number_id' => ['required', 'string', 'max:80'],
            'waba_id' => ['required', 'string', 'max:80'],
            'access_token' => ['nullable', 'string'],
            'app_id' => ['nullable', 'string', 'max:80'],
            'app_secret' => ['nullable', 'string'],
            'verify_token' => ['nullable', 'string', 'max:160'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $now = now();
        $payload = [
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'business_name' => $validated['business_name'] ?? null,
            'phone_number' => $validated['phone_number'],
            'phone_number_id' => $validated['phone_number_id'],
            'waba_id' => $validated['waba_id'],
            'app_id' => $validated['app_id'] ?? env('META_EMBEDDED_SIGNUP_APP_ID', env('META_WA_APP_ID')),
            'verify_token' => $validated['verify_token'] ?? Str::random(48),
            'status' => 'manual',
            'is_default' => $request->boolean('is_default'),
            'updated_at' => $now,
        ];

        if (!empty($validated['access_token'])) {
            $payload['access_token'] = Crypt::encryptString($validated['access_token']);
        }

        if (!empty($validated['app_secret'])) {
            $payload['app_secret'] = Crypt::encryptString($validated['app_secret']);
        }

        if ($payload['is_default']) {
            DB::table('whatsapp_cloud_channels')
                ->where('user_id', $request->user()->id)
                ->update(['is_default' => false]);
        }

        DB::table('whatsapp_cloud_channels')->updateOrInsert(
            [
                'user_id' => $request->user()->id,
                'phone_number_id' => $validated['phone_number_id'],
            ],
            $payload + ['created_at' => $now]
        );

        return back()->with('alert', [
            'type' => 'success',
            'msg' => 'Channel WhatsApp Cloud API berhasil disimpan.',
        ]);
    }

    public function embeddedCallback(Request $request)
    {
        $payload = $request->all();
        $event = $request->input('event', 'embedded_signup_callback');
        $user = $request->user();

        DB::table('whatsapp_embedded_signup_logs')->insert([
            'user_id' => optional($user)->id,
            'event' => $event,
            'payload' => json_encode($payload),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Sesi user tidak ditemukan.'], 401);
        }

        $session = $request->input('session', []);
        $response = $request->input('response', []);
        $authResponse = data_get($response, 'authResponse', []);
        $code = data_get($authResponse, 'code') ?: $request->input('code');
        $wabaId = data_get($session, 'data.waba_id') ?: data_get($session, 'waba_id') ?: $request->input('waba_id');
        $phoneNumberId = data_get($session, 'data.phone_number_id') ?: data_get($session, 'phone_number_id') ?: $request->input('phone_number_id');
        $phoneNumber = data_get($session, 'data.phone_number') ?: data_get($session, 'phone_number') ?: 'pending-meta';
        $businessName = data_get($session, 'data.business_name') ?: data_get($session, 'business_name');

        $appId = env('META_EMBEDDED_SIGNUP_APP_ID', env('META_WA_APP_ID'));
        $appSecret = env('META_EMBEDDED_SIGNUP_APP_SECRET', env('META_WA_APP_SECRET'));
        $graphVersion = env('META_WA_API_VERSION', 'v25.0');
        $token = null;
        $tokenExchangeStatus = 'skipped_no_code_or_secret';

        if ($code && $appId && $appSecret) {
            $tokenResponse = Http::asForm()->get("https://graph.facebook.com/{$graphVersion}/oauth/access_token", [
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'code' => $code,
            ]);

            DB::table('whatsapp_embedded_signup_logs')->insert([
                'user_id' => $user->id,
                'event' => 'embedded_signup_token_exchange',
                'payload' => json_encode([
                    'ok' => $tokenResponse->successful(),
                    'status' => $tokenResponse->status(),
                    'body' => $tokenResponse->json(),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($tokenResponse->successful()) {
                $token = $tokenResponse->json('access_token');
                $tokenExchangeStatus = $token ? 'exchanged' : 'missing_access_token';
            } else {
                $tokenExchangeStatus = 'failed';
            }
        }

        if (!$wabaId || !$phoneNumberId) {
            return response()->json([
                'status' => true,
                'saved' => false,
                'message' => 'Callback diterima, tetapi WABA ID atau Phone Number ID belum dikirim Meta.',
                'token_exchange' => $tokenExchangeStatus,
            ]);
        }

        $now = now();
        $channel = [
            'user_id' => $user->id,
            'name' => $businessName ?: 'WhatsApp Cloud API',
            'business_name' => $businessName,
            'phone_number' => $phoneNumber,
            'phone_number_id' => $phoneNumberId,
            'waba_id' => $wabaId,
            'app_id' => $appId,
            'verify_token' => env('META_WA_WEBHOOK_VERIFY_TOKEN') ?: Str::random(48),
            'status' => $token ? 'connected' : 'embedded_pending_token',
            'webhook_status' => 'ready',
            'is_default' => true,
            'updated_at' => $now,
        ];

        if ($token) {
            $channel['access_token'] = Crypt::encryptString($token);
        }

        DB::table('whatsapp_cloud_channels')
            ->where('user_id', $user->id)
            ->update(['is_default' => false]);

        DB::table('whatsapp_cloud_channels')->updateOrInsert(
            [
                'user_id' => $user->id,
                'phone_number_id' => $phoneNumberId,
            ],
            $channel + ['created_at' => $now]
        );

        return response()->json([
            'status' => true,
            'saved' => true,
            'message' => $token ? 'Channel Meta berhasil terhubung.' : 'Channel Meta tersimpan, tetapi token belum otomatis karena App Secret belum tersedia atau code tidak dikirim.',
            'token_exchange' => $tokenExchangeStatus,
        ]);
    }

    public function destroyCloud(Request $request, int $id)
    {
        DB::table('whatsapp_cloud_channels')
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->delete();

        return back()->with('alert', [
            'type' => 'success',
            'msg' => 'Channel WhatsApp Cloud API diputuskan dari aplikasi.',
        ]);
    }
}
