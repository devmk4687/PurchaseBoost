<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Models\AgentLog;
use Illuminate\Http\Request;
use App\Services\PlannerService;
use App\Services\ExecutionService;


class AgentController extends Controller
{
   protected $plannerservice;
   protected $executionservice;
   public function __construct(PlannerService $plannerservice,
   ExecutionService $executionservice){
      $this->plannerservice = $plannerservice;
      $this->executionservice = $executionservice;
   }
   public function run(Request $request){
    //set the goal from request
    $goal = $request->input('goal');

    //make the plan by planner service into steps
    //$plan = app(PlannerService::class)->createPlan($goal);
    $plan = $this->plannerservice->createPlan($goal);

    //execute the plan using executer service
    //$results = app(ExecutionService::class)->execute($plan);
    $results = $this->executionservice->execute($plan);

    //dtore the Agent response log
    AgentLog::create([
         'goal' => $goal,
         'plan' => json_encode($plan),
         'result' => json_encode($results)
      ]);

      //return the response
     return response()->json([
            'goal' => $goal,
            'plan' => $plan,
            'result' => $results
        ]);

      
   }
}
