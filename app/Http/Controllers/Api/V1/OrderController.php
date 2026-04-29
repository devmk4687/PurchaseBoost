<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Contracts\TierAwardServiceInterface;
use App\Services\OrderReturnService;
use App\Services\CampaignPointsService;
use App\Services\OrderCreationService;
use App\Models\Order;
use App\Models\Transaction;

class OrderController extends Controller
{
    //create the instance variable for Tier Service
    protected $tierService;

    //create the instance variable for Order return service. 
    protected $returnService;

    //create the instance variable for Campaign Points
    protected $campPointsService;
    protected $orderCreationService;

    public function index(Request $request){
        $order = Order::query()
            ->with(['transactions' => function ($q) {
            $q->select('id','orderId','credit','debit');
            }])
            ->when($request->custId, fn($q) => $q->where('custId', $request->custId))
            ->when($request->orderId, fn($q) => $q->where('orderId', $request->orderId));
            

        $orders = $order->paginate(10);

        return response()->json($orders);
    }

    public function __construct(TierAwardServiceInterface $tierService,
    OrderReturnService $returnService,
    CampaignPointsService $campPointsService,
    OrderCreationService $orderCreationService
        ) {
        $this->tierService = $tierService;
        $this->returnService = $returnService;
        $this->campPointsService = $campPointsService;
        $this->orderCreationService = $orderCreationService;
    }

    public function store(Request $request){
        $validated = $request->validate([
            'custId' => ['required', 'integer'],
            'orderId' => ['required', 'string'],
            'price' => ['required', 'numeric'],
            'orderStatus' => ['required', 'integer'],
            'orderDate' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'orderDetails' => ['nullable'],
        ]);

        $result = $this->orderCreationService->create($validated);

        return response()->json(
            ['message' => $result['message']] + $result,
            $result['status'] === 'created' ? 201 : 200
        );
    }

    public function returnOrder(Request $request, Order $order){

        $returnAmount = $request->price;

        $tier = $this->returnService->processReturn($order,$returnAmount);

        return response()->json([
            'message' => 'Order returned',
            'updatedTier' => $tier
        ]);

    }
}
