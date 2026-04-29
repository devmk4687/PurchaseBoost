<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CommunicationTemplate;
use App\Models\CustomerSegment;
use App\Models\LoyaltyMember;
use App\Services\CampaignExecutionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CampaignController extends Controller
{
    public function __construct(private CampaignExecutionService $campaignExecutionService)
    {
    }

    private function resolveStatus(Request $request): string
    {
        return $request->boolean('is_active')
            ? Campaign::STATUS_PUBLISHED
            : Campaign::STATUS_DRAFT;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $campaigns = Campaign::withCount('communicationLogs')->latest()->paginate(10);

         return view('campaign.index', compact('campaigns'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
         return view('campaign.create', $this->formData(new Campaign([
            'status' => Campaign::STATUS_DRAFT,
         ])));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $this->validatedCampaign($request);

        Campaign::create($this->campaignPayload($validated, $request));

        return redirect()->route('campaigns.index')->with('success', 'Campaign created!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Campaign  $campaign
     * @return \Illuminate\Http\Response
     */
    public function show(Campaign $campaign)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Campaign  $campaign
     * @return \Illuminate\Http\Response
     */
    public function edit(Campaign $campaign)
    {
        return view('campaign.edit', $this->formData($campaign));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Campaign  $campaign
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Campaign $campaign)
    {
        $validated = $this->validatedCampaign($request);

        $campaign->update($this->campaignPayload($validated, $request));

        return redirect()->route('campaigns.index')->with('success', 'Campaign updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Campaign  $campaign
     * @return \Illuminate\Http\Response
     */
    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return redirect()->back()->with('success', 'Deleted!');
    }


    public function toggleStatus(Campaign $campaign)
    {
        $campaign->status = $campaign->is_active
            ? Campaign::STATUS_DRAFT
            : Campaign::STATUS_PUBLISHED;
        $campaign->save();

        return redirect()->back();
    }

    public function run(Campaign $campaign)
    {
        try {
            $execution = $this->campaignExecutionService->execute($campaign);
        } catch (ValidationException $exception) {
            return redirect()
                ->back()
                ->withErrors($exception->errors());
        }

        $summary = $execution['result'];

        return redirect()
            ->route('campaigns.edit', $campaign)
            ->with('success', "Campaign executed. Sent: {$summary['sent']}, Failed: {$summary['failed']}.");
    }

    private function validatedCampaign(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'config.step1.segment_id' => ['nullable', 'integer', 'exists:customer_segments,id'],
            'config.step1.customer_ids' => ['nullable', 'array'],
            'config.step1.customer_ids.*' => ['integer', 'exists:customer_details,id'],
            'config.step2.channel' => ['required', Rule::in(['email', 'sms', 'whatsapp'])],
            'config.step2.template_id' => ['required', 'integer', 'exists:communication_templates,id'],
        ]);

        $selectedCustomerIds = collect($validated['config']['step1']['customer_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->all();

        $segmentId = $validated['config']['step1']['segment_id'] ?? null;

        if ($segmentId) {
            $segmentCustomerIds = CustomerSegment::findOrFail((int) $segmentId)
                ->members()
                ->pluck('customer_details.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $selectedCustomerIds = array_values(array_unique(array_merge($selectedCustomerIds, $segmentCustomerIds)));
        }

        if ($selectedCustomerIds === []) {
            throw ValidationException::withMessages([
                'config.step1.customer_ids' => 'Please select at least one loyalty member or choose a saved segment.',
            ]);
        }

        $validated['config']['step1']['customer_ids'] = $selectedCustomerIds;

        $template = CommunicationTemplate::find($validated['config']['step2']['template_id']);

        if ($template && $template->channel !== $validated['config']['step2']['channel']) {
            throw ValidationException::withMessages([
                'config.step2.template_id' => 'Selected template does not belong to the selected channel.',
            ]);
        }

        return $validated;
    }

    private function campaignPayload(array $validated, Request $request): array
    {
        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['config']['step2']['channel'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'config' => [
                'step1' => [
                    'customer_ids' => array_map('intval', $validated['config']['step1']['customer_ids']),
                    'segment_id' => isset($validated['config']['step1']['segment_id'])
                        ? (int) $validated['config']['step1']['segment_id']
                        : null,
                ],
                'step2' => [
                    'channel' => $validated['config']['step2']['channel'],
                    'template_id' => (int) $validated['config']['step2']['template_id'],
                ],
                'step3' => $request->input('config.step3', []),
            ],
            'status' => $this->resolveStatus($request),
        ];
    }

    private function formData(Campaign $campaign): array
    {
        $config = $campaign->config ?? [];
        $selectedCustomerIds = collect(old('config.step1.customer_ids', $config['step1']['customer_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->all();
        $selectedSegmentId = (int) old('config.step1.segment_id', $config['step1']['segment_id'] ?? 0);
        $selectedChannel = old('config.step2.channel', $config['step2']['channel'] ?? $campaign->type ?? 'email');
        $selectedTemplateId = (int) old('config.step2.template_id', $config['step2']['template_id'] ?? 0);

        $templates = CommunicationTemplate::query()
            ->orderBy('channel')
            ->orderBy('name')
            ->get();

        $logs = $campaign->exists
            ? $campaign->communicationLogs()->with(['customer', 'template'])->latest()->limit(20)->get()
            : collect();

        return [
            'campaign' => $campaign,
            'customers' => LoyaltyMember::orderBy('firstName')->orderBy('lastName')->get(),
            'segments' => class_exists(CustomerSegment::class)
                ? CustomerSegment::with(['members:id,customerId,firstName,lastName,email'])->withCount('members')->latest()->get()
                : collect(),
            'templates' => $templates,
            'selectedCustomerIds' => $selectedCustomerIds,
            'selectedSegmentId' => $selectedSegmentId,
            'selectedChannel' => $selectedChannel,
            'selectedTemplateId' => $selectedTemplateId,
            'logs' => $logs,
        ];
    }
}
