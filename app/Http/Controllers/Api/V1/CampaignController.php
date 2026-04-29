<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CommunicationLog;
use App\Models\CommunicationTemplate;
use App\Models\LoyaltyMember;
use App\Services\CampaignExecutionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    public function __construct(private CampaignExecutionService $campaignExecutionService)
    {
    }

    public function index()
    {
        $campaigns = Campaign::query()
            ->withCount('communicationLogs')
            ->latest()
            ->paginate(10);

        return response()->json($campaigns);
    }

    public function store(Request $request)
    {
        $validated = $this->validateCampaign($request);

        $campaign = Campaign::create($this->campaignPayload($validated));

        return response()->json($this->campaignResponse($campaign), 201);
    }

    public function show(Campaign $campaign)
    {
        return response()->json($this->campaignResponse($campaign));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $this->validateCampaign($request, true);

        $campaign->update($this->campaignPayload($validated, $campaign));

        return response()->json($this->campaignResponse($campaign->fresh()));
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return response()->json(['message' => 'Campaign deleted successfully.']);
    }

    public function customers(Request $request)
    {
        $customers = LoyaltyMember::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($nestedQuery) use ($search) {
                    $nestedQuery->where('customerId', 'like', '%' . $search . '%')
                        ->orWhere('firstName', 'like', '%' . $search . '%')
                        ->orWhere('lastName', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone1', 'like', '%' . $search . '%')
                        ->orWhere('company', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json($customers);
    }

    public function logs(Campaign $campaign)
    {
        $logs = $campaign->communicationLogs()
            ->with(['customer:id,customerId,firstName,lastName,email,phone1', 'template:id,name,channel'])
            ->latest()
            ->paginate(20);

        return response()->json($logs);
    }

    public function publish(Campaign $campaign)
    {
        $execution = $this->campaignExecutionService->execute($campaign);

        return response()->json([
            'message' => 'Campaign execution completed.',
            'campaign' => $this->campaignResponse($execution['campaign']),
            'result' => $execution['result'],
        ]);
    }

    private function validateCampaign(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        $validated = $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string', Rule::in(['email', 'sms', 'whatsapp'])],
            'start_date' => [$required, 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'config' => [$required, 'array'],
            'config.step1' => [$required, 'array'],
            'config.step1.customer_ids' => [$required, 'array', 'min:1'],
            'config.step1.customer_ids.*' => ['integer', 'exists:customer_details,id'],
            'config.step2' => [$required, 'array'],
            'config.step2.channel' => [$required, Rule::in(['email', 'sms', 'whatsapp'])],
            'config.step2.template_id' => [$required, 'integer', 'exists:communication_templates,id'],
        ]);

        $template = CommunicationTemplate::find($validated['config']['step2']['template_id']);

        if ($template && $template->channel !== $validated['config']['step2']['channel']) {
            throw ValidationException::withMessages([
                'config.step2.template_id' => 'Selected template channel does not match campaign channel.',
            ]);
        }

        return $validated;
    }

    private function campaignPayload(array $validated, ?Campaign $campaign = null): array
    {
        $config = $validated['config'] ?? ($campaign?->config ?? []);

        return [
            'name' => $validated['name'] ?? $campaign?->name,
            'description' => $validated['description'] ?? $campaign?->description,
            'type' => $validated['config']['step2']['channel'] ?? $validated['type'] ?? $campaign?->type,
            'start_date' => $validated['start_date'] ?? $campaign?->start_date,
            'end_date' => $validated['end_date'] ?? $campaign?->end_date,
            'config' => $config,
            'status' => $campaign?->status ?? Campaign::STATUS_DRAFT,
        ];
    }

    private function campaignResponse(Campaign $campaign): array
    {
        $campaign->loadMissing('communicationLogs');

        $config = $campaign->config ?? [];
        $customerIds = $config['step1']['customer_ids'] ?? [];
        $templateId = $config['step2']['template_id'] ?? null;

        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'description' => $campaign->description,
            'type' => $campaign->type,
            'status' => $campaign->status,
            'start_date' => $campaign->start_date,
            'end_date' => $campaign->end_date,
            'config' => $config,
            'customers' => LoyaltyMember::whereIn('id', $customerIds)
                ->get(['id', 'customerId', 'firstName', 'lastName', 'email', 'phone1', 'company']),
            'template' => $templateId
                ? CommunicationTemplate::find($templateId)
                : null,
            'logs_count' => $campaign->communicationLogs->count(),
        ];
    }

}
