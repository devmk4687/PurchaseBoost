<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CommunicationTemplate;
use App\Models\LoyaltyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CampaignFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_campaign_with_step_configuration()
    {
        Sanctum::actingAs(User::factory()->create());

        $customer = LoyaltyMember::create([
            'customerId' => 'CUST001',
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'company' => 'Acme',
            'city' => 'Chennai',
            'country' => 'India',
            'phone1' => '9999999999',
            'phone2' => '',
            'email' => 'jane@example.com',
            'subscriptionDate' => '2026-04-01',
            'website' => 'https://example.com',
        ]);

        $template = CommunicationTemplate::create([
            'name' => 'Welcome Email',
            'channel' => 'email',
            'subject' => 'Hello {{firstName}}',
            'body' => 'Welcome {{firstName}} to {{company}}',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/campaigns', [
            'name' => 'April Outreach',
            'description' => 'Three step flow',
            'start_date' => '2026-04-09',
            'end_date' => '2026-04-30',
            'config' => [
                'step1' => [
                    'customer_ids' => [$customer->id],
                ],
                'step2' => [
                    'channel' => 'email',
                    'template_id' => $template->id,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('config.step1.customer_ids.0', $customer->id)
            ->assertJsonPath('config.step2.template_id', $template->id);

        $this->assertDatabaseHas('campaigns', [
            'name' => 'April Outreach',
            'type' => 'email',
        ]);
    }

    public function test_publish_sends_email_and_stores_log()
    {
        Sanctum::actingAs(User::factory()->create());
        Mail::fake();
        Http::fake();

        $customer = LoyaltyMember::create([
            'customerId' => 'CUST002',
            'firstName' => 'John',
            'lastName' => 'Smith',
            'company' => 'Acme',
            'city' => 'Bengaluru',
            'country' => 'India',
            'phone1' => '8888888888',
            'phone2' => '',
            'email' => 'john@example.com',
            'subscriptionDate' => '2026-04-01',
            'website' => 'https://example.com',
        ]);

        $template = CommunicationTemplate::create([
            'name' => 'Promo Email',
            'channel' => 'email',
            'subject' => 'Offer for {{firstName}}',
            'body' => 'Hi {{firstName}}, welcome to {{company}}',
            'is_active' => true,
        ]);

        $campaign = Campaign::create([
            'name' => 'Promo Flow',
            'description' => 'Email flow',
            'type' => 'email',
            'start_date' => '2026-04-09',
            'end_date' => '2026-04-30',
            'status' => Campaign::STATUS_DRAFT,
            'config' => [
                'step1' => [
                    'customer_ids' => [$customer->id],
                ],
                'step2' => [
                    'channel' => 'email',
                    'template_id' => $template->id,
                ],
            ],
        ]);

        $response = $this->postJson("/api/v1/campaign/{$campaign->id}/publish");

        $response->assertOk()
            ->assertJsonPath('result.sent', 1)
            ->assertJsonPath('result.failed', 0);

        $this->assertDatabaseHas('communication_logs', [
            'campaign_id' => $campaign->id,
            'customer_detail_id' => $customer->id,
            'channel' => 'email',
            'status' => 'sent',
        ]);
    }
}
