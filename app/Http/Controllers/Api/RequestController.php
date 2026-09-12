<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RequestResource;
use App\Models\Request as RequestModel;
use App\Services\RequestService;
use Illuminate\Http\Request;
use App\Support\Phone;

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
        $request->load([
            'responsible',
            'statusHistory' => fn ($query) => $query->orderBy('id'),
            'statusHistory.changedBy',
        ]);

        return new RequestResource($request);
    }

    // POST /api/requests — создать заявку
    public function store(Request $request, RequestService $service)
    {
        $request->merge(['phone' => Phone::normalize($request->input('phone'))]);

        $data = $request->validate([
            'source' => 'required|string',
            'phone' => ['required', 'regex:/^7\d{10}$/'],
            'problem' => 'required|string|max:5000',
            'client_name' => 'nullable|string|max:255',
            'car_info' => 'nullable|string|max:255',
            'urgency' => 'nullable|string|in:today,soon,planned,emergency',
            'files' => 'nullable|array',
        ], [
            'phone.required' => 'Укажите телефон в формате +7 999 123-45-67.',
            'phone.regex' => 'Укажите телефон в формате +7 999 123-45-67.',
        ]);

        $newRequest = $service->create($data);

        return new RequestResource($newRequest);
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
