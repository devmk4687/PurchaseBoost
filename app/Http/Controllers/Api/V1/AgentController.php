<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AgentLog;
use Illuminate\Http\Request;
use App\Services\PlannerService;
use App\Services\ExecutionService;
use Illuminate\Validation\ValidationException;


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
        $payload = $request->validate([
            'goal' => ['required', 'string'],
            'channel' => ['nullable', 'in:email,sms,whatsapp'],
            'customer_ids' => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['integer', 'exists:customer_details,id'],
            'template_id' => ['nullable', 'integer', 'exists:communication_templates,id'],
            'send_now' => ['nullable', 'boolean'],
        ]);

        $payload['channel'] = $payload['channel'] ?? 'email';
        $payload['send_now'] = (bool) ($payload['send_now'] ?? false);

        $plan = $this->plannerservice->createPlan($payload);

        try {
            $results = $this->executionservice->execute($plan);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Campaign agent execution failed.',
                'errors' => $exception->errors(),
                'plan' => $plan,
            ], 422);
        }

        AgentLog::create([
            'goal' => $payload['goal'],
            'plan' => json_encode($plan),
            'result' => json_encode($results)
        ]);

        return response()->json([
            'goal' => $payload['goal'],
            'plan' => $plan,
            'result' => $results
        ]);
   }
}
