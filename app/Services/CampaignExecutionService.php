<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CommunicationLog;
use App\Models\CommunicationTemplate;
use App\Models\LoyaltyMember;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CampaignExecutionService
{
    public function execute(Campaign $campaign): array
    {
        $campaign = $campaign->fresh();
        $config = $campaign->config ?? [];
        $step1 = $config['step1'] ?? [];
        $step2 = $config['step2'] ?? [];

        $customerIds = $step1['customer_ids'] ?? [];
        $channel = $step2['channel'] ?? null;
        $templateId = $step2['template_id'] ?? null;

        if (empty($customerIds) || ! $channel || ! $templateId) {
            throw ValidationException::withMessages([
                'campaign' => 'Campaign configuration is incomplete. Step 1 and Step 2 are required before running.',
            ]);
        }

        $template = CommunicationTemplate::findOrFail($templateId);

        if ($template->channel !== $channel) {
            throw ValidationException::withMessages([
                'campaign' => 'Selected template channel does not match campaign channel.',
            ]);
        }

        $customers = LoyaltyMember::whereIn('id', $customerIds)->get();

        if ($customers->isEmpty()) {
            throw ValidationException::withMessages([
                'campaign' => 'No customers found for this campaign.',
            ]);
        }

        $results = [
            'total' => $customers->count(),
            'sent' => 0,
            'failed' => 0,
            'logs' => [],
        ];

        foreach ($customers as $customer) {
            $delivery = $this->sendCampaignMessage($campaign, $template, $customer, $channel);
            $results['logs'][] = $delivery;
            $results[$delivery['status'] === 'sent' ? 'sent' : 'failed']++;
        }

        $config['step3'] = [
            'executed_at' => now()->toDateTimeString(),
            'summary' => [
                'total' => $results['total'],
                'sent' => $results['sent'],
                'failed' => $results['failed'],
            ],
        ];

        $campaign->update([
            'status' => Campaign::STATUS_PUBLISHED,
            'config' => $config,
        ]);

        return [
            'campaign' => $campaign->fresh(),
            'result' => $results,
        ];
    }

    private function sendCampaignMessage(
        Campaign $campaign,
        CommunicationTemplate $template,
        LoyaltyMember $customer,
        string $channel
    ): array {
        $subject = $this->renderTemplate($template->subject, $customer);
        $message = $this->renderTemplate($template->body, $customer);
        $recipient = $this->resolveRecipient($customer, $channel);
        $status = 'failed';
        $errorMessage = null;

        if (! $recipient) {
            $errorMessage = 'Recipient is missing for the selected channel.';
        } else {
            try {
                if ($channel === CommunicationTemplate::CHANNEL_EMAIL) {
                    Mail::send([], [], function ($mail) use ($customer, $subject, $message) {
                        $html = $this->prepareEmailHtml($message, $mail);

                        $mail->to($customer->email, trim($customer->firstName . ' ' . $customer->lastName))
                            ->subject($subject ?: 'Campaign Message')
                            ->html($html);
                    });

                    $status = 'sent';
                } elseif ($channel === CommunicationTemplate::CHANNEL_SMS) {
                    [$status, $errorMessage] = $this->sendWebhookMessage('sms', $recipient, $message, $subject);
                } elseif ($channel === CommunicationTemplate::CHANNEL_WHATSAPP) {
                    [$status, $errorMessage] = $this->sendWebhookMessage('whatsapp', $recipient, $message, $subject);
                }
            } catch (\Throwable $exception) {
                $errorMessage = $exception->getMessage();
            }
        }

        $log = CommunicationLog::create([
            'campaign_id' => $campaign->id,
            'communication_template_id' => $template->id,
            'customer_detail_id' => $customer->id,
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'message' => $message,
            'status' => $status,
            'error_message' => $errorMessage,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);

        return [
            'customer_id' => $customer->id,
            'recipient' => $recipient,
            'status' => $status,
            'error_message' => $errorMessage,
            'log_id' => $log->id,
        ];
    }

    private function sendWebhookMessage(string $serviceKey, string $recipient, string $message, ?string $subject): array
    {
        if ($serviceKey === 'sms' && config('services.sms.provider') === 'twilio') {
            return $this->sendTwilioSms($recipient, $message);
        }

        if ($serviceKey === 'whatsapp' && config('services.whatsapp.provider') === 'twilio') {
            return $this->sendTwilioWhatsapp($recipient, $message);
        }

        $url = config("services.{$serviceKey}.webhook_url");
        $token = config("services.{$serviceKey}.token");

        if (! $url) {
            return ['failed', strtoupper($serviceKey) . ' webhook is not configured.'];
        }

        $request = Http::acceptJson();

        if ($token) {
            $request = $request->withToken($token);
        }

        $response = $request->post($url, [
            'recipient' => $recipient,
            'subject' => $subject,
            'message' => $message,
            'channel' => $serviceKey,
        ]);

        if ($response->successful()) {
            return ['sent', null];
        }

        return ['failed', $response->body() ?: 'Webhook request failed.'];
    }

    private function sendTwilioSms(string $recipient, string $message): array
    {
        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');
        $from = config('services.twilio.from');
        $messagingServiceSid = config('services.twilio.messaging_service_sid');

        if (! $accountSid || ! $authToken || (! $from && ! $messagingServiceSid)) {
            return ['failed', 'Twilio SMS is not fully configured. Set TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, and TWILIO_FROM or TWILIO_MESSAGING_SERVICE_SID.'];
        }

        $payload = [
            'To' => $this->normalizePhoneNumber($recipient),
            'Body' => trim(strip_tags($message)),
        ];

        if ($messagingServiceSid) {
            $payload['MessagingServiceSid'] = $messagingServiceSid;
        } else {
            $payload['From'] = $this->normalizePhoneNumber($from);
        }

        $response = Http::asForm()
            ->withBasicAuth($accountSid, $authToken)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", $payload);

        if ($response->successful()) {
            return ['sent', null];
        }

        $error = data_get($response->json(), 'message') ?: $response->body() ?: 'Twilio SMS request failed.';

        return ['failed', $error];
    }

    private function sendTwilioWhatsapp(string $recipient, string $message): array
    {
        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');
        $from = config('services.twilio.whatsapp_from');
        $body = $this->prepareWhatsappBody($message);
        $mediaUrls = $this->extractWhatsappMediaUrls($message);

        if (! $accountSid || ! $authToken || ! $from) {
            return ['failed', 'Twilio WhatsApp is not fully configured. Set TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, and TWILIO_WHATSAPP_FROM.'];
        }

        if ($body === '' && empty($mediaUrls)) {
            return ['failed', 'WhatsApp message body is empty and no media attachments were found.'];
        }

        $payload = [
            'To' => $this->normalizeWhatsappAddress($recipient),
            'From' => $this->normalizeWhatsappAddress($from),
        ];

        if ($body !== '') {
            $payload['Body'] = $body;
        }

        if (! empty($mediaUrls)) {
            // Free-form WhatsApp delivery supports one media object per message.
            $payload['MediaUrl'] = $mediaUrls[0];
        }

        $response = Http::asForm()
            ->withBasicAuth($accountSid, $authToken)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", $payload);

        if ($response->successful()) {
            return ['sent', null];
        }

        $error = data_get($response->json(), 'message') ?: $response->body() ?: 'Twilio WhatsApp request failed.';

        return ['failed', $error];
    }

    private function resolveRecipient(LoyaltyMember $customer, string $channel): ?string
    {
        return match ($channel) {
            CommunicationTemplate::CHANNEL_EMAIL => $customer->email,
            CommunicationTemplate::CHANNEL_SMS,
            CommunicationTemplate::CHANNEL_WHATSAPP => $customer->phone1 ?: $customer->phone2,
            default => null,
        };
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        $phoneNumber = trim($phoneNumber);

        if ($phoneNumber === '') {
            return $phoneNumber;
        }

        if (Str::startsWith($phoneNumber, '+')) {
            return '+' . preg_replace('/\D+/', '', substr($phoneNumber, 1));
        }

        return '+' . preg_replace('/\D+/', '', $phoneNumber);
    }

    private function normalizeWhatsappAddress(string $phoneNumber): string
    {
        $normalizedNumber = $this->normalizePhoneNumber(
            Str::startsWith($phoneNumber, 'whatsapp:')
                ? substr($phoneNumber, 9)
                : $phoneNumber
        );

        return 'whatsapp:' . $normalizedNumber;
    }

    private function prepareWhatsappBody(?string $message): string
    {
        $plainText = trim(html_entity_decode(strip_tags((string) $message), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return preg_replace('/\s+/', ' ', $plainText) ?? $plainText;
    }

    private function extractWhatsappMediaUrls(?string $message): array
    {
        if (! $message) {
            return [];
        }

        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $message, $matches);

        if (empty($matches[1])) {
            return [];
        }

        $mediaUrls = [];

        foreach ($matches[1] as $source) {
            $url = $this->resolvePublicMediaUrl($source);

            if ($url) {
                $mediaUrls[] = $url;
            }
        }

        return array_values(array_unique($mediaUrls));
    }

    private function resolvePublicMediaUrl(string $source): ?string
    {
        $source = trim($source);

        if ($source === '' || Str::startsWith($source, 'cid:') || Str::startsWith($source, 'data:')) {
            return null;
        }

        if (Str::startsWith($source, ['http://', 'https://'])) {
            return $this->rewriteToPublicAssetUrl($source) ?? $source;
        }

        $publicBaseUrl = rtrim((string) (config('app.asset_url') ?: config('app.url')), '/');

        if ($publicBaseUrl === '') {
            return null;
        }

        if (Str::startsWith($source, '//')) {
            $scheme = parse_url($publicBaseUrl, PHP_URL_SCHEME) ?: 'https';

            return $scheme . ':' . $source;
        }

        if (Str::startsWith($source, '/')) {
            return $publicBaseUrl . $source;
        }

        return $publicBaseUrl . '/' . ltrim($source, '/');
    }

    private function rewriteToPublicAssetUrl(string $source): ?string
    {
        $publicBaseUrl = rtrim((string) config('app.asset_url'), '/');

        if ($publicBaseUrl === '') {
            return null;
        }

        $host = strtolower((string) parse_url($source, PHP_URL_HOST));

        if (! in_array($host, ['localhost', '127.0.0.1'], true)) {
            return null;
        }

        $path = parse_url($source, PHP_URL_PATH);

        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, '/PBbackend/public/')) {
            return $publicBaseUrl . substr($path, strlen('/PBbackend/public'));
        }

        if (Str::startsWith($path, '/uploads/')) {
            return $publicBaseUrl . $path;
        }

        return $publicBaseUrl . '/' . ltrim($path, '/');
    }

    private function renderTemplate(?string $content, LoyaltyMember $customer): ?string
    {
        if ($content === null) {
            return null;
        }

        return strtr($content, [
            '{{customerId}}' => $customer->customerId,
            '{{firstName}}' => $customer->firstName,
            '{{lastName}}' => $customer->lastName,
            '{{company}}' => $customer->company,
            '{{city}}' => $customer->city,
            '{{country}}' => $customer->country,
            '{{phone1}}' => $customer->phone1,
            '{{phone2}}' => $customer->phone2,
            '{{email}}' => $customer->email,
            '{{website}}' => $customer->website,
        ]);
    }

    private function prepareEmailHtml(?string $message, $mail): string
    {
        $html = $message ?: '';

        return preg_replace_callback(
            '/<img[^>]+src=["\']([^"\']+)["\']/i',
            function (array $matches) use ($mail) {
                $source = $matches[1];
                $path = $this->resolveEmbeddableImagePath($source);

                if (! $path || ! is_file($path)) {
                    return $matches[0];
                }

                $cid = $mail->embed($path);

                return str_replace($source, $cid, $matches[0]);
            },
            $html
        ) ?? $html;
    }

    private function resolveEmbeddableImagePath(string $source): ?string
    {
        $source = trim($source);

        if ($source === '' || Str::startsWith($source, 'cid:') || Str::startsWith($source, 'data:')) {
            return null;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        $parsedPath = parse_url($source, PHP_URL_PATH);
        $templateImagePath = $this->resolveTemplateImageStoragePath($parsedPath ?: $source);

        if (Str::startsWith($source, '/')) {
            if ($templateImagePath) {
                return $templateImagePath;
            }

            return public_path(ltrim($source, '/'));
        }

        if ($appUrl !== '' && Str::startsWith($source, $appUrl) && $parsedPath) {
            if ($templateImagePath) {
                return $templateImagePath;
            }

            return public_path(ltrim($parsedPath, '/'));
        }

        if ($parsedPath && Str::contains($parsedPath, '/uploads/message-templates/')) {
            return public_path(ltrim($parsedPath, '/'));
        }

        if ($templateImagePath) {
            return $templateImagePath;
        }

        return null;
    }

    private function resolveTemplateImageStoragePath(string $path): ?string
    {
        $path = trim($path);

        if ($path === '' || ! preg_match('#/message-templates/images/([A-Za-z0-9._-]+)$#', $path, $matches)) {
            return null;
        }

        $filename = basename($matches[1]);
        $storagePath = storage_path('app/public/message-templates/' . $filename);

        return is_file($storagePath) ? $storagePath : null;
    }
}
