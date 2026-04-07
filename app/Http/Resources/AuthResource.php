<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Data\Auth\{AuthResultData, RefreshResultData};
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// ─────────────────────────────────────────────────────────
// AuthResource — respuesta completa del login/refresh
// ─────────────────────────────────────────────────────────

class AuthResource extends JsonResource
{
    public static function fromLoginResult(AuthResultData $result): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'user'         => new UserResource($result->user),
                'access_token' => $result->access_token,
                'token_type'   => $result->token_type,
                'expires_in'   => $result->expires_in,
            ],
        ]);
    }

    public static function fromRefreshResult(RefreshResultData $result): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'access_token' => $result->access_token,
                'token_type'   => $result->token_type,
                'expires_in'   => $result->expires_in,
            ],
        ]);
    }

    public function toArray(Request $request): array { return []; }
}
