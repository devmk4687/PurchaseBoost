<?php

namespace App\Services;

use App\Models\CommunicationTemplate;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class PlannerService
{
    public function createPlan(array $payload): array
    {
        $goal = trim((string) ($payload['goal'] ?? ''));
        $channel = $payload['channel'] ?? CommunicationTemplate::CHANNEL_EMAIL;
        $customerIds = array_values(array_map('intval', $payload['customer_ids'] ?? []));
        $templateId = isset($payload['template_id']) ? (int) $payload['template_id'] : null;
        $sendNow = (bool) ($payload['send_now'] ?? false);

        $generated = $this->generateCampaignContent($goal, $channel, $sendNow);

        return [
            'goal' => $goal,
            'channel' => $channel,
            'send_now' => $sendNow,
            'customer_ids' => $customerIds,
            'template_id' => $templateId,
            'campaign' => [
                'name' => $generated['name'] ?? $this->fallbackName($goal),
                'description' => $generated['description'] ?? $goal,
                'subject' => $generated['subject'] ?? null,
                'body' => $generated['body'] ?? $goal,
            ],
            'agents' => [
                [
                    'name' => 'strategy_agent',
                    'responsibility' => 'Turn the user request into a campaign brief.',
                    'status' => 'completed',
                ],
                [
                    'name' => 'content_agent',
                    'responsibility' => 'Draft the email subject and body for the selected channel.',
                    'status' => 'completed',
                ],
                [
                    'name' => 'delivery_agent',
                    'responsibility' => 'Validate audience, persist the campaign, and optionally trigger delivery.',
                    'status' => 'pending',
                ],
            ],
        ];
    }

    private function generateCampaignContent(string $goal, string $channel, bool $sendNow): array
    {
        $apiKey = env('OPENAI_API_KEY');

        if (! $apiKey) {
            return $this->fallbackContent($goal, $channel, $sendNow);
        }

        $prompt = implode("\n", [
            'You are a marketing campaign planner.',
            'Return only JSON with keys: name, description, subject, body.',
            'Channel: ' . $channel,
            'Send now: ' . ($sendNow ? 'yes' : 'no'),
            'Goal: ' . $goal,
            'Body must be concise and suitable for a customer communication.',
        ]);

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/responses', [
                'model' => 'gpt-4.1-mini',
                'input' => $prompt,
            ]);

        if (! $response->successful()) {
            return $this->fallbackContent($goal, $channel, $sendNow);
        }

        $data = $response->json();
        $outputText = trim((string) Arr::get($data, 'output.0.content.0.text', ''));
        $decoded = json_decode($outputText, true);

        if (! is_array($decoded)) {
            return $this->fallbackContent($goal, $channel, $sendNow);
        }

        return [
            'name' => trim((string) ($decoded['name'] ?? '')) ?: $this->fallbackName($goal),
            'description' => trim((string) ($decoded['description'] ?? '')) ?: $goal,
            'subject' => trim((string) ($decoded['subject'] ?? '')) ?: $this->fallbackSubject($goal),
            'body' => trim((string) ($decoded['body'] ?? '')) ?: $this->fallbackBody($goal, $channel, $sendNow),
        ];
    }

    private function fallbackContent(string $goal, string $channel, bool $sendNow): array
    {
        return [
            'name' => $this->fallbackName($goal),
            'description' => $goal,
            'subject' => $this->fallbackSubject($goal),
            'body' => $this->fallbackBody($goal, $channel, $sendNow),
        ];
    }

    private function fallbackName(string $goal): string
    {
        return mb_substr($goal !== '' ? $goal : 'New Campaign Request', 0, 80);
    }

    private function fallbackSubject(string $goal): string
    {
        return 'Campaign Update: ' . mb_substr($goal !== '' ? $goal : 'Your latest offer', 0, 60);
    }

    private function fallbackBody(string $goal, string $channel, bool $sendNow): string
    {
        $intro = $channel === CommunicationTemplate::CHANNEL_EMAIL
            ? 'Hello {{firstName}},'
            : 'Hi {{firstName}},';

        $timing = $sendNow
            ? 'This message is ready to go out immediately.'
            : 'This message is prepared for the next campaign send.';

        return implode("\n\n", [
            $intro,
            $goal !== '' ? $goal : 'We have an update for you.',
            $timing,
            'Thank you for being a valued member.',
        ]);
    }
}
