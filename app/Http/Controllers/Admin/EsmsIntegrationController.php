<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EsmsIntegrationController extends Controller
{
    public function index(): View
    {
        return view('theme::pages.admin.esms-integration', [
            'allowedOrigins' => implode("\n", $this->allowedOrigins()),
            'ssoSecretFilled' => trim((string) env('ESMS_WAPI_SSO_SECRET', '')) !== '',
            'appUrl' => url('/'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'allowed_origins' => ['required', 'string', 'max:4000'],
        ]);

        $origins = $this->normalizeOrigins((string) $validated['allowed_origins']);

        if ($origins === []) {
            return back()
                ->withInput()
                ->with('alert', [
                    'type' => 'danger',
                    'msg' => 'Minimal isi satu domain e-SMS yang valid.',
                ]);
        }

        setEnv('ESMS_ALLOWED_FRAME_ANCESTORS', implode(',', $origins));

        return back()->with('alert', [
            'type' => 'success',
            'msg' => 'Domain e-SMS yang diizinkan berhasil disimpan.',
        ]);
    }

    private function allowedOrigins(): array
    {
        $configured = trim((string) env('ESMS_ALLOWED_FRAME_ANCESTORS', ''));

        return $this->normalizeOrigins($configured !== '' ? $configured : implode(',', [
            'https://esms.dl-edu.my.id',
            'https://e-sms.dl-edu.my.id',
            'https://e-sms.vpntunnel.my.id',
        ]));
    }

    private function normalizeOrigins(string $value): array
    {
        $origins = preg_split('/[\r\n,]+/', $value) ?: [];

        return collect($origins)
            ->map(fn (string $origin): string => rtrim(trim($origin), '/'))
            ->filter(fn (string $origin): bool => preg_match('#^https?://[a-z0-9.-]+(?::[0-9]+)?$#i', $origin) === 1)
            ->unique()
            ->values()
            ->all();
    }
}
