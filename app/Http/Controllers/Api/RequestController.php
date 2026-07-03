<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RequestResource;
use App\Models\Request as RequestModel;
use App\Services\RequestService;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    // GET /api/requests — список всех заявок, новые сверху
    public function index()
    {
        return RequestResource::collection(
            RequestModel::orderBy('created_at', 'desc')->get()
        );
    }

    // GET /api/requests/{id} — одна заявка
    public function show(RequestModel $request)
    {
        return new RequestResource($request);
    }

    // POST /api/requests — создать заявку
    public function store(Request $request, RequestService $service)
    {
        $data = $request->validate([
            'source' => 'required|string',
            'phone' => 'required|string',
            'problem' => 'required|string',
            'client_name' => 'nullable|string',
            'car_info' => 'nullable|string',
            'urgency' => 'nullable|string',
            'files' => 'nullable|array',
        ]);

        $newRequest = $service->create($data);

        return response()->json($newRequest, 201);
    }

    public function updateStatus(Request $request, RequestModel $requestModel)
    {
        $data = $request->validate([
            'status' => 'required|string|in:new,contacted,assigned,processing,success,lost,follow_up', // только валидные статусы
        ]);

        $requestModel->update($data);

        return new RequestResource($requestModel);
    }
}
