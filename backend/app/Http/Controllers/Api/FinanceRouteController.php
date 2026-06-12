<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinanceHistory;
use App\Models\FinanceRoute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceRouteController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(FinanceRoute::query()->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $route = FinanceRoute::query()->create($this->validated($request));
        $this->recordHistory('route', $route->id, 'Creada', $route->name);

        return response()->json($route, 201);
    }

    public function update(Request $request, FinanceRoute $financeRoute): JsonResponse
    {
        $financeRoute->update($this->validated($request));
        $this->recordHistory('route', $financeRoute->id, 'Editada', $financeRoute->name);

        return response()->json($financeRoute);
    }

    public function destroy(FinanceRoute $financeRoute): JsonResponse
    {
        $name = $financeRoute->name;
        $id = $financeRoute->id;
        $financeRoute->delete();
        $this->recordHistory('route', $id, 'Eliminada', $name);

        return response()->json(status: 204);
    }

    public function history(): JsonResponse
    {
        return response()->json(
            FinanceHistory::query()->where('entity_type', 'route')->latest()->get()
        );
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'distance' => ['nullable', 'integer', 'min:0'],
            'base_rate' => ['required', 'numeric', 'min:0'],
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
