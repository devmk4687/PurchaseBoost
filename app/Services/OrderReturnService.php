<?php
namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use App\Contracts\TierAwardServiceInterface;

class OrderReturnService
{
    protected $tierService;

    public function __construct(TierAwardServiceInterface $tierService)
    {
        $this->tierService = $tierService;
    }

    public function processReturn(Order $order, float $returnAmount)
    {
        $customerId = $order->custId;

        // mark order returned
        $order->update([
            'orderStatus' => 2,
            'price' => $returnAmount
        ]);

        Transaction::create([
            "custId"=>$customerId,
            "orderId"=>$order->orderId,
            "credit"=>0,
            "debit"=>$returnAmount
        ]);

        $totalCreditPoints = Transaction::where('custId',$customerId)->sum('credit');

        $totalDebitPoints = Transaction::where('custId',$customerId)->sum('debit');

        $balance = $totalCreditPoints - $totalDebitPoints;

        // determine new tier
        $tier = $this->tierService->determineTier($balance);

        // update all customer orders tier (or customer table if you have one)
        Order::where('custId', $order->custId)
            ->update(['tierStatus' => $tier]);

        return $tier;
    }
}
