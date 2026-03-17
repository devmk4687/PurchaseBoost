<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Contracts\TierAwardServiceInterface;
use App\Services\OrderReturnService;
use App\Services\CampaignPointsService;
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
    CampaignPointsService $campPointsService
        ) {
        $this->tierService = $tierService;
        $this->returnService = $returnService;
        $this->campPointsService = $campPointsService;
    }

    public function store(Request $request){

        if(Order::where('orderId',$request->orderId)->exists()){
            return response()->json([
            'message' => 'Order already present',
            ], 200);
        }

        $customerId = $request->custId;

        //campaign set or not check and recalculate the order points
        if(!$this->campPointsService->processCampaign($request)){
            $orderPoints = $request->price;
        }else{
            $orderPoints = $this->campPointsService->processCampaign($request);
        }
        //Transaction can manage the cunstomers order points
        $transaction = Transaction::create([
            "custId"=>$customerId,
            "orderId"=>$request->orderId,
            "credit"=>$orderPoints,
            "debit"=>0
        ]);

        $totalCreditPoints = Transaction::where('custId',$customerId)->sum('credit');

        $totalDebitPoints = Transaction::where('custId',$customerId)->sum('debit');

        $balance = $totalCreditPoints - $totalDebitPoints;

        $orderDetailsString = json_encode($request->orderDetails);
        
        $order = Order::create([
            "price"=>$request->price,
            "custId"=>$customerId,
            "orderId"=>$request->orderId,
            "orderStatus"=>$request->orderStatus,
            "description"=>$orderDetailsString,
            "created_at"=>$request->orderDate
        ]);
        

        $tier = $this->tierService->determineTier($balance);

        $order->update([
            "tierStatus"=>$tier
        ]);

       return response()->json([
            'message' => 'Order created',
            'transactionId' => $transaction->id,
            'orderCreationId' => $order->id,
            'orderPoints'=> $orderPoints,
            'balance'=> $balance,
            "tier"=>$tier
            ], 201);
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
