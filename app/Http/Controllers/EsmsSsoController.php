<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\EsmsWapiAccess;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class EsmsSsoController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $payload = $this->verifiedPayload($request);

        if (! $payload) {
            return redirect('/login')->withErrors(['email' => 'Link akses e-SMS ke WAPI tidak valid atau sudah kedaluwarsa.']);
        }

        $request->session()->put('esms_wapi_sso_payload', $payload);

        if (Auth::check() && strtolower((string) Auth::user()->email) === $payload['email']) {
            return $this->grantAccess($request, $payload);
        }

        $googleConfig = $this->googleConfig();

        if (! $googleConfig) {
            return redirect('/login')->withErrors(['email' => 'Login Google WAPI belum dikonfigurasi. Lengkapi GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, dan GOOGLE_REDIRECT_URI.']);
        }

        $state = Str::random(48);
        $request->session()->put('esms_wapi_google_state', $state);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => $googleConfig['client_id'],
            'redirect_uri' => $googleConfig['redirect_uri'],
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
            'prompt' => 'select_account',
            'access_type' => 'online',
        ], '', '&', PHP_QUERY_RFC3986));
    }

    public function callback(Request $request): RedirectResponse
    {
        $payload = $request->session()->pull('esms_wapi_sso_payload');
        $expectedState = $request->session()->pull('esms_wapi_google_state');

        if (! is_array($payload)) {
            return redirect('/login')->withErrors(['email' => 'Sesi akses e-SMS ke WAPI sudah habis. Silakan buka ulang dari e-SMS.']);
        }

        if (! $expectedState || ! hash_equals($expectedState, (string) $request->query('state'))) {
            return redirect('/login')->withErrors(['email' => 'Validasi login Google gagal. Silakan buka ulang dari e-SMS.']);
        }

        $googleProfile = $this->fetchGoogleProfile((string) $request->query('code'));

        if (! $googleProfile) {
            return redirect('/login')->withErrors(['email' => 'WAPI gagal membaca profil Google. Silakan coba lagi.']);
        }

        $googleEmail = strtolower((string) ($googleProfile['email'] ?? ''));

        if ($googleEmail !== $payload['email']) {
            return redirect('/login')->withErrors(['email' => 'Akun Google tidak sama dengan user yang diberi akses dari e-SMS.']);
        }

        $payload['google_id'] = (string) ($googleProfile['sub'] ?? '');

        return $this->grantAccess($request, $payload);
    }

    private function verifiedPayload(Request $request): ?array
    {
        $secret = trim((string) env('ESMS_WAPI_SSO_SECRET', ''));

        if ($secret === '') {
            return null;
        }

        $payload = $request->query();
        $signature = (string) ($payload['signature'] ?? '');
        unset($payload['signature']);

        foreach (['app', 'aud', 'esms_user_id', 'email', 'iat', 'exp', 'nonce'] as $required) {
            if (blank($payload[$required] ?? null)) {
                return null;
            }
        }

        if ((int) $payload['exp'] < now()->timestamp || (int) $payload['iat'] > now()->addMinute()->timestamp) {
            return null;
        }

        ksort($payload);
        $canonical = http_build_query($payload, '', '&', PHP_QUERY_RFC3986);
        $expectedSignature = hash_hmac('sha256', $canonical, $secret);
        $rawCanonical = $this->rawCanonicalQueryWithoutSignature($request);
        $rawExpectedSignature = $rawCanonical
            ? hash_hmac('sha256', $rawCanonical, $secret)
            : null;

        if (
            ! hash_equals($expectedSignature, $signature)
            && (! $rawExpectedSignature || ! hash_equals($rawExpectedSignature, $signature))
        ) {
            return null;
        }

        $nonceKey = 'esms-wapi-sso-nonce:'.$payload['nonce'];

        if (! Cache::add($nonceKey, true, now()->addMinutes(10))) {
            return null;
        }

        $payload['email'] = strtolower((string) $payload['email']);
        $payload['device'] = preg_replace('/\D+/', '', (string) ($payload['device'] ?? '')) ?: null;

        return $payload;
    }

    private function rawCanonicalQueryWithoutSignature(Request $request): ?string
    {
        $rawQuery = (string) $request->server('QUERY_STRING', '');

        if ($rawQuery === '') {
            return null;
        }

        $parts = array_values(array_filter(
            explode('&', $rawQuery),
            fn (string $part) => ! str_starts_with($part, 'signature=')
        ));

        return $parts ? implode('&', $parts) : null;
    }

    private function fetchGoogleProfile(string $code): ?array
    {
        $googleConfig = $this->googleConfig();

        if (! $googleConfig || $code === '') {
            return null;
        }

        $tokenResponse = Http::asForm()->timeout(20)->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $googleConfig['client_id'],
            'client_secret' => $googleConfig['client_secret'],
            'redirect_uri' => $googleConfig['redirect_uri'],
            'grant_type' => 'authorization_code',
        ]);

        if (! $tokenResponse->successful()) {
            return null;
        }

        $accessToken = (string) ($tokenResponse->json('access_token') ?? '');

        if ($accessToken === '') {
            return null;
        }

        $profileResponse = Http::timeout(20)
            ->withToken($accessToken)
            ->get('https://openidconnect.googleapis.com/v1/userinfo');

        return $profileResponse->successful() ? $profileResponse->json() : null;
    }

    private function grantAccess(Request $request, array $payload): RedirectResponse
    {
        $user = User::query()->where('email', $payload['email'])->first();

        if (! $user) {
            return redirect('/login')->withErrors(['email' => 'User WAPI untuk email ini belum tersedia. Hubungi admin WAPI.']);
        }

        $access = EsmsWapiAccess::query()->updateOrCreate(
            [
                'email' => $payload['email'],
                'device_number' => $payload['device'],
            ],
            [
                'esms_user_id' => $payload['esms_user_id'],
                'google_id' => $payload['google_id'] ?? null,
                'name' => $payload['name'] ?? $user->username ?? $user->email,
                'role' => $payload['role'] ?? 'device_user',
                'is_active' => true,
                'last_login_at' => now(),
                'metadata' => [
                    'source' => 'e-sms',
                    'return_url' => $payload['return_url'] ?? null,
                ],
            ]
        );

        if (! $access->is_active) {
            return redirect('/login')->withErrors(['email' => 'Akses WAPI untuk akun ini sedang nonaktif.']);
        }

        Auth::login($user);

        if ($payload['device']) {
            $device = Device::query()
                ->where('user_id', $user->getKey())
                ->where('body', $payload['device'])
                ->first();

            if ($device) {
                $request->session()->put('selectedDevice', [
                    'device_id' => $device->id,
                    'device_body' => $device->body,
                ]);
            }
        }

        return redirect('/id/devices');
    }

    private function googleConfig(): ?array
    {
        $clientId = trim((string) env('GOOGLE_CLIENT_ID', ''));
        $clientSecret = trim((string) env('GOOGLE_CLIENT_SECRET', ''));
        $redirectUri = trim((string) env('GOOGLE_REDIRECT_URI', url('/sso/esms/google/callback')));

        if ($clientId === '' || $clientSecret === '' || $redirectUri === '') {
            return null;
        }

        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
        ];
    }
}
