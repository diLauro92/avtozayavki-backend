<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TelegramLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramLinkController extends Controller
{
    // POST /api/telegram/link — выдать одноразовую ссылку на привязку
    public function store(Request $request, TelegramLinkService $service): JsonResponse
    {
        return response()->json([
            'link' => $service->issueLink($request->user()),
        ]);
    }

    // DELETE /api/telegram/link — отвязать аккаунт
    public function destroy(Request $request, TelegramLinkService $service): JsonResponse
    {
        $service->unlink($request->user());

        return response()->json(['message' => 'Привязка удалена']);
    }
}
