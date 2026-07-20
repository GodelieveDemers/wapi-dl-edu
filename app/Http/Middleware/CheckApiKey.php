<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Http\Middleware;

use App\Models\Device;
use App\Models\User;
use App\Repositories\DeviceRepository;
use App\Services\Common;
use App\Utils\CacheKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $deviceRepository = new DeviceRepository();
            $authorizationToken = trim((string) $request->bearerToken());
            $apiKey = trim((string) $request->input('api_key', ''));
            $deviceApiToken = trim((string) $request->input('device_api_token', ''));
            $resolvedToken = $deviceApiToken !== ''
                ? $deviceApiToken
                : ($authorizationToken !== '' ? $authorizationToken : $apiKey);
            $sender = trim((string) $request->input('sender', ''));

            if ($resolvedToken === '' || $sender === '') {
                return response()->json(
                    [
                        'status' => false,
                        'msg' => __('Invalid api_key or sender,please check again'),
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

			$device = $deviceRepository->byBody($sender)->single();

            if (! $device) {
                return response()->json(
                    [
                        'status' => false,
                        'msg' => __('Invalid api_key or sender,please check again'),
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $user = null;
            $authMode = 'legacy_user_api_key';

            if (! empty($device->api_token) && hash_equals((string) $device->api_token, $resolvedToken)) {
                $user = $device->user;
                $authMode = 'device_api_token';
            } else {
			    $user = User::where('api_key', $resolvedToken)->first();
            }

            if (! $user) {
                return response()->json(
                    [
                        'status' => false,
                        'msg' => __('Invalid api_key or sender,please check again'),
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if ($device->user_id != $user->id) {
                return response()->json(
                    [
                        'status' => false, 'msg' => __('Invalid api_key or sender,please check again'),
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

			if ($user->level != 'admin' && env("ENABLE_INDEX") == 'yes' && empty($user->plan_data['api'])) {
				return response()->json(
                    [
                        'status' => false, 'msg' => __('You do not have permission to use this feature, Please purchase/upgrade your plan'),
                    ],
                    Response::HTTP_BAD_REQUEST
                );
			}
            $request->merge([
                'device' => $device,
                'user' => $user,
                'auth_mode' => $authMode,
                'resolved_api_token' => $resolvedToken,
            ]);
            return $next($request);
        } catch (\Throwable $th) {
            return response()->json(
                [
                    'status' => false,
                    'msg' => __('Invali api_key or sender,please check again (2)'),
                ],
                Response::HTTP_BAD_REQUEST
            );
        }
    }
}
