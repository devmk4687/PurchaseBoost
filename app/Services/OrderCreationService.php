<?php

namespace App\Services;

use App\Contracts\TierAwardServiceInterface;
use App\Models\LoyaltyMember;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCreationService
{
    public function __construct(
        private TierAwardServiceInterface $tierService,
        private CampaignPointsService $campaignPointsService
    ) {
    }

    public function create(array $payload): array
    {
        $orderId = (string) $payload['orderId'];

        if (Order::where('orderId', $orderId)->exists()) {
            return [
                'status' => 'duplicate',
                'message' => 'Order already present',
                'orderId' => $orderId,
            ];
        }

        return DB::transaction(function () use ($payload, $orderId) {
            $customerId = $this->resolveCustomerId($payload);
            $orderDate = $payload['orderDate'];
            $orderPoints = $this->campaignPointsService->processCampaign((object) [
                'orderDate' => $orderDate,
                'price' => $payload['price'],
            ]);

            $orderPoints = $orderPoints ?: $payload['price'];

            $transaction = Transaction::create([
                'custId' => $customerId,
                'orderId' => $orderId,
                'credit' => $orderPoints,
                'debit' => 0,
                'description' => $payload['description'] ?? null,
                'meta_data' => isset($payload['orderDetails']) ? json_encode($payload['orderDetails']) : null,
            ]);

            $balance = (float) Transaction::where('custId', $customerId)->sum('credit')
                - (float) Transaction::where('custId', $customerId)->sum('debit');

            $order = Order::create([
                'price' => $payload['price'],
                'custId' => $customerId,
                'orderId' => $orderId,
                'orderStatus' => (int) $payload['orderStatus'],
                'description' => $this->normalizeDescription($payload),
                'created_at' => $orderDate,
            ]);

            $tier = $this->tierService->determineTier($balance);

            $order->update([
                'tierStatus' => $tier,
            ]);

            return [
                'status' => 'created',
                'message' => 'Order created',
                'transactionId' => $transaction->id,
                'orderCreationId' => $order->id,
                'orderPoints' => $orderPoints,
                'balance' => $balance,
                'tier' => $tier,
                'orderId' => $orderId,
            ];
        });
    }

    private function resolveCustomerId(array $payload): int
    {
        if (! empty($payload['customerId'])) {
            $member = LoyaltyMember::where('customerId', (string) $payload['customerId'])->first();

            if (! $member) {
                throw ValidationException::withMessages([
                    'customerId' => 'Customer ID ' . $payload['customerId'] . ' was not found in loyalty members.',
                ]);
            }

            return (int) $member->id;
        }

        if (! empty($payload['custId']) && ctype_digit((string) $payload['custId'])) {
            $member = LoyaltyMember::find((int) $payload['custId']);

            if (! $member) {
                throw ValidationException::withMessages([
                    'custId' => 'Customer record with internal ID ' . $payload['custId'] . ' was not found.',
                ]);
            }

            return (int) $member->id;
        }

        if (! empty($payload['custId'])) {
            $member = LoyaltyMember::where('customerId', (string) $payload['custId'])->first();

            if (! $member) {
                throw ValidationException::withMessages([
                    'custId' => 'Customer ID ' . $payload['custId'] . ' was not found in loyalty members.',
                ]);
            }

            return (int) $member->id;
        }

        throw ValidationException::withMessages([
            'customerId' => 'A customer identifier is required.',
        ]);
    }

    private function normalizeDescription(array $payload): ?string
    {
        if (! empty($payload['orderDetails'])) {
            return json_encode($payload['orderDetails']);
        }

        return $payload['description'] ?? null;
    }
}
