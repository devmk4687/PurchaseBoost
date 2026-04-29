<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CommunicationTemplate;
use App\Models\LoyaltyMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExecutionService
{
    public function __construct(private CampaignExecutionService $campaignExecutionService)
    {
    }

    public function execute(array $plan): array
    {
        $customerIds = array_values(array_unique(array_map('intval', $plan['customer_ids'] ?? [])));
        $channel = $plan['channel'] ?? CommunicationTemplate::CHANNEL_EMAIL;
        $goal = $plan['goal'] ?? '';

        if ($customerIds === []) {
            throw ValidationException::withMessages([
                'customer_ids' => 'At least one loyalty member is required to generate a campaign.',
            ]);
        }

        $customers = LoyaltyMember::whereIn('id', $customerIds)->get();

        if ($customers->isEmpty()) {
            throw ValidationException::withMessages([
                'customer_ids' => 'Selected loyalty members were not found.',
            ]);
        }

        return DB::transaction(function () use ($plan, $customers, $customerIds, $channel, $goal): array {
            $template = $this->resolveTemplate($plan, $channel);
            $campaignData = $plan['campaign'] ?? [];
            $startDate = now()->toDateString();
            $endDate = now()->addDays(7)->toDateString();

            if (! $template) {
                $template = CommunicationTemplate::create([
                    'name' => $campaignData['name'] ?? 'Generated Campaign Template',
                    'channel' => $channel,
                    'subject' => $channel === CommunicationTemplate::CHANNEL_EMAIL
                        ? ($campaignData['subject'] ?? ('Campaign Update: ' . mb_substr($goal, 0, 60)))
                        : null,
                    'body' => $campaignData['body'] ?? $goal,
                    'is_active' => true,
                ]);
            }

            $campaign = Campaign::create([
                'name' => $campaignData['name'] ?? 'Generated Campaign',
                'description' => $campaignData['description'] ?? $goal,
                'type' => $channel,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'config' => [
                    'step1' => [
                        'customer_ids' => $customerIds,
                    ],
                    'step2' => [
                        'channel' => $channel,
                        'template_id' => $template->id,
                    ],
                    'agent' => [
                        'goal' => $goal,
                        'agents' => $plan['agents'] ?? [],
                    ],
                ],
                'status' => Campaign::STATUS_DRAFT,
            ]);

            $executionResult = null;

            if (! empty($plan['send_now'])) {
                $executionResult = $this->campaignExecutionService->execute($campaign);
                $campaign = $executionResult['campaign'];
            }

            return [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'template_id' => $template->id,
                'audience_count' => $customers->count(),
                'send_now' => (bool) ($plan['send_now'] ?? false),
                'delivery' => $executionResult['result'] ?? null,
            ];
        });
    }

    private function resolveTemplate(array $plan, string $channel): ?CommunicationTemplate
    {
        $templateId = $plan['template_id'] ?? null;

        if (! $templateId) {
            return null;
        }

        $template = CommunicationTemplate::find($templateId);

        if (! $template) {
            throw ValidationException::withMessages([
                'template_id' => 'Selected communication template was not found.',
            ]);
        }

        if ($template->channel !== $channel) {
            throw ValidationException::withMessages([
                'template_id' => 'Selected communication template does not match the requested channel.',
            ]);
        }

        return $template;
    }
}
