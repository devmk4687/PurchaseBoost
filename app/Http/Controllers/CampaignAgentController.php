<?php

namespace App\Http\Controllers;

use App\Models\AgentLog;
use App\Models\CommunicationTemplate;
use App\Models\CustomerSegment;
use App\Models\LoyaltyMember;
use App\Services\ExecutionService;
use App\Services\PlannerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CampaignAgentController extends Controller
{
    public function __construct(
        private PlannerService $plannerService,
        private ExecutionService $executionService
    ) {
    }

    public function index(): View
    {
        return view('campaign-agent.index', [
            'customers' => LoyaltyMember::orderBy('firstName')->orderBy('lastName')->get(),
            'segments' => CustomerSegment::with(['members:id,customerId,firstName,lastName,email'])
                ->withCount('members')
                ->latest()
                ->get(),
            'templates' => CommunicationTemplate::where('is_active', true)->orderBy('channel')->orderBy('name')->get(),
            'agentResult' => session('agent_result'),
            'agentLogs' => AgentLog::latest()->paginate(10, ['*'], 'history_page'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'goal' => ['required', 'string'],
            'channel' => ['required', 'in:email,sms,whatsapp'],
            'segment_id' => ['nullable', 'integer', 'exists:customer_segments,id'],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['integer', 'exists:customer_details,id'],
            'template_id' => ['nullable', 'integer', 'exists:communication_templates,id'],
            'send_now' => ['nullable', 'boolean'],
        ]);

        $payload['send_now'] = $request->boolean('send_now');
        $payload['customer_ids'] = array_values(array_unique(array_map('intval', $payload['customer_ids'] ?? [])));

        if ($request->filled('segment_id')) {
            $segmentMemberIds = CustomerSegment::findOrFail($request->integer('segment_id'))
                ->members()
                ->pluck('customer_details.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $payload['customer_ids'] = array_values(array_unique(array_merge($payload['customer_ids'], $segmentMemberIds)));
        }

        if ($payload['customer_ids'] === []) {
            return redirect()
                ->route('campaign-agent.index')
                ->withInput()
                ->withErrors(['customer_ids' => 'Please select at least one loyalty member or choose a saved segment.']);
        }

        $plan = $this->plannerService->createPlan($payload);

        try {
            $result = $this->executionService->execute($plan);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('campaign-agent.index')
                ->withInput()
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('campaign-agent.index')
            ->with('success', 'Campaign agent request completed successfully.')
            ->with('agent_result', [
                'plan' => $plan,
                'result' => $result,
            ]);
    }
}
