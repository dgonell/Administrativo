<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinanceQuote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceQuoteController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            FinanceQuote::query()->with(['client', 'lines'])->latest()->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $quote = DB::transaction(function () use ($data) {
            $lines = $data['lines'];
            unset($data['lines']);

            $quote = FinanceQuote::query()->create($data + ['number' => $this->nextNumber()]);
            $quote->lines()->createMany($lines);

            return $quote->load(['client', 'lines']);
        });

        return response()->json($quote, 201);
    }

    public function update(Request $request, FinanceQuote $financeQuote): JsonResponse
    {
        $data = $this->validated($request);

        $quote = DB::transaction(function () use ($data, $financeQuote) {
            $lines = $data['lines'];
            unset($data['lines']);

            $financeQuote->update($data);
            $financeQuote->lines()->delete();
            $financeQuote->lines()->createMany($lines);

            return $financeQuote->load(['client', 'lines']);
        });

        return response()->json($quote);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'finance_client_id' => ['nullable', 'exists:finance_clients,id'],
            'status' => ['required', 'in:draft,sent,approved,rejected,expired'],
            'service_date' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'final_price' => ['required', 'numeric', 'min:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.route_name' => ['required', 'string', 'max:255'],
            'lines.*.capacity' => ['required', 'string', 'max:60'],
            'lines.*.days' => ['required', 'integer', 'min:1'],
            'lines.*.buses' => ['required', 'integer', 'min:1'],
            'lines.*.price_per_bus' => ['required', 'numeric', 'min:0'],
            'lines.*.final_price' => ['required', 'numeric', 'min:0'],
            'lines.*.pickup_point' => ['nullable', 'string', 'max:255'],
            'lines.*.dropoff_point' => ['nullable', 'string', 'max:255'],
            'lines.*.schedule' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function nextNumber(): string
    {
        $next = (FinanceQuote::query()->max('id') ?? 0) + 1;

        return 'COT-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
