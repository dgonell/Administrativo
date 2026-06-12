<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinanceClient;
use App\Models\FinanceHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceClientController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(FinanceClient::query()->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $client = FinanceClient::query()->create($this->validated($request));
        $this->recordHistory('client', $client->id, 'Creado', $client->name);

        return response()->json($client, 201);
    }

    public function update(Request $request, FinanceClient $financeClient): JsonResponse
    {
        $financeClient->update($this->validated($request));
        $this->recordHistory('client', $financeClient->id, 'Editado', $financeClient->name);

        return response()->json($financeClient);
    }

    public function destroy(FinanceClient $financeClient): JsonResponse
    {
        $name = $financeClient->name;
        $id = $financeClient->id;
        $financeClient->delete();
        $this->recordHistory('client', $id, 'Eliminado', $name);

        return response()->json(status: 204);
    }

    public function history(): JsonResponse
    {
        return response()->json(
            FinanceHistory::query()->where('entity_type', 'client')->latest()->get()
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rnc' => ['nullable', 'string', 'max:30'],
            'contact' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);
    }

    private function recordHistory(string $type, ?int $id, string $action, string $name): void
    {
        FinanceHistory::query()->create([
            'entity_type' => $type,
            'entity_id' => $id,
            'action' => $action,
            'name' => $name,
        ]);
    }
}
