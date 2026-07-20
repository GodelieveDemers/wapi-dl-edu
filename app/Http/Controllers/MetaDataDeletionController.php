<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MetaDataDeletionController extends Controller
{
    public function status(string $code)
    {
        $request = DB::table('meta_data_deletion_requests')
            ->where('confirmation_code', $code)
            ->first();

        return view('theme::pages.legal.data-deletion-status', compact('request', 'code'));
    }

    public function callback(Request $request)
    {
        $signedRequest = $request->input('signed_request');

        if (!$signedRequest) {
            return response()->json(['error' => 'signed_request is required'], 400);
        }

        $payload = $this->parseSignedRequest($signedRequest);

        if (!$payload) {
            return response()->json(['error' => 'invalid signed_request'], 400);
        }

        $code = 'meta-del-' . Str::uuid()->toString();

        DB::table('meta_data_deletion_requests')->insert([
            'meta_user_id' => $payload['user_id'] ?? null,
            'confirmation_code' => $code,
            'status' => 'received',
            'payload' => json_encode($payload),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'url' => url('/meta/data-deletion/status/' . $code),
            'confirmation_code' => $code,
        ]);
    }

    private function parseSignedRequest(string $signedRequest): ?array
    {
        [$encodedSignature, $encodedPayload] = array_pad(explode('.', $signedRequest, 2), 2, null);

        if (!$encodedSignature || !$encodedPayload) {
            return null;
        }

        $secret = env('META_EMBEDDED_SIGNUP_APP_SECRET', env('META_WA_APP_SECRET'));

        if (!$secret) {
            return null;
        }

        $signature = $this->base64UrlDecode($encodedSignature);
        $payloadJson = $this->base64UrlDecode($encodedPayload);
        $payload = json_decode($payloadJson, true);

        if (!is_array($payload)) {
            return null;
        }

        $expectedSignature = hash_hmac('sha256', $encodedPayload, $secret, true);

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        return $payload;
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }
}
