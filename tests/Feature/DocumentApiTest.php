<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\Spk;
use App\Models\User;
use App\Services\DocumentApi\ProposalContentCatalog;
use Database\Seeders\ProposalContentDefaultSeeder;
use Database\Seeders\SpkContentDefaultSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DocumentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $apiUser;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiUser = User::factory()->create([
            'email' => 'api-author@example.test',
        ]);
        $this->company = $this->makeCompany();

        config([
            'document_api.key' => 'test-api-key',
            'document_api.user_id' => $this->apiUser->id,
        ]);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        config(['document_api.key' => 'test-api-key']);

        $this->getJson('/api/v1')->assertUnauthorized();
        $this->withHeader('X-Api-Key', 'wrong')
            ->getJson('/api/v1')
            ->assertUnauthorized();
    }

    public function test_disabled_api_key_is_rejected(): void
    {
        config(['document_api.key' => '']);

        $this->withToken('test-api-key')
            ->getJson('/api/v1')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Document API is not configured.');
    }

    public function test_index_guide_openapi_and_skeletons_are_available(): void
    {
        $this->seed(ProposalContentDefaultSeeder::class);

        $this->apiGet('/api/v1')
            ->assertOk()
            ->assertJsonPath('version', 'v1')
            ->assertJsonPath('guide_url', url('/api/v1/guide'));

        $this->apiGet('/api/v1/guide')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('Kanjo Document API', false)
            ->assertSee('Agent Operating Manual', false)
            ->assertSee('renewal_date', false)
            ->assertSee('dry_run', false)
            ->assertSee('/clients/{id}', false);

        $this->apiGet('/api/v1/openapi.json')
            ->assertOk()
            ->assertJsonPath('openapi', '3.0.3');

        $this->apiGet('/api/v1/proposals/skeleton')
            ->assertOk()
            ->assertJsonPath('payload.content.brief.mode', 'default')
            ->assertJsonPath('payload.content.faq.mode', 'default');

        $this->apiGet('/api/v1/companies')
            ->assertOk()
            ->assertJsonPath('data.0.id', $this->company->id)
            ->assertJsonPath('data.0.pic.0.pic_name', 'Company PIC Alpha');
    }

    public function test_proposal_create_requires_every_content_field(): void
    {
        $response = $this->apiPost('/api/v1/proposals', [
            'company_id' => $this->company->id,
            'client' => [
                'company' => 'PT Contoh',
                'name' => 'Budi',
            ],
            'content' => [
                'brief' => ['mode' => 'default'],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('skeleton', (string) $response->json('hint'));
        $missing = $response->json('missing_content_fields');
        $this->assertContains('faq', $missing);
        $this->assertContains('features', $missing);
    }

    public function test_proposal_dry_run_does_not_persist(): void
    {
        $this->seed(ProposalContentDefaultSeeder::class);

        $this->apiPost('/api/v1/proposals', $this->proposalPayload([
            'dry_run' => true,
        ]))
            ->assertOk()
            ->assertJsonPath('dry_run', true)
            ->assertJsonPath('valid', true)
            ->assertJsonPath('would_create.type', 'proposal')
            ->assertJsonPath('would_create.client.action', 'create');

        $this->assertSame(0, Proposal::query()->count());
        $this->assertSame(0, Client::query()->count());
    }

    public function test_proposal_create_publishes_applies_defaults_and_creates_client(): void
    {
        $this->seed(ProposalContentDefaultSeeder::class);

        $response = $this->apiPost('/api/v1/proposals', $this->proposalPayload())
            ->assertOk();

        $proposal = Proposal::query()->find($response->json('data.id'));
        $this->assertInstanceOf(Proposal::class, $proposal);
        $this->assertSame(DocumentStatus::PUBLISHED, $proposal->status);
        $this->assertSame($this->apiUser->id, $proposal->user_id);
        $this->assertSame('PT Contoh', $proposal->client_company);
        $this->assertNotNull($proposal->client_id);
        $this->assertSame($proposal->client_id, $response->json('data.client_id'));
        $this->assertStringContainsString('Thank you for requesting a quotation', $proposal->getTranslation('brief', 'en'));
        $this->assertStringContainsString('What do we need to prepare', $proposal->getTranslation('faq', 'en'));
        $this->assertNotNull($response->json('data.public_url'));
        $this->assertStringContainsString('/proposal/', (string) $response->json('data.public_url'));
        $this->assertSame(1, Client::query()->count());
    }

    public function test_proposal_override_converts_markdown_and_empty_skips_defaults(): void
    {
        $this->seed(ProposalContentDefaultSeeder::class);

        $payload = $this->proposalPayload();
        $payload['content']['brief'] = [
            'mode' => 'override',
            'value' => [
                'en' => '## Custom brief',
                'id' => '## Brief kustom',
            ],
        ];
        $payload['content']['faq'] = ['mode' => 'empty'];

        $response = $this->apiPost('/api/v1/proposals', $payload)->assertOk();
        $proposal = Proposal::query()->find($response->json('data.id'));

        $this->assertStringContainsString('<h2>Custom brief</h2>', $proposal->getTranslation('brief', 'en'));
        $this->assertSame('', $proposal->getTranslation('faq', 'en'));
        $this->assertSame('', $proposal->getTranslation('faq', 'id'));
        $this->assertStringContainsString('Professional custom design', $proposal->getTranslation('core_services', 'en'));
    }

    public function test_editing_client_does_not_change_proposal_snapshot(): void
    {
        $this->seed(ProposalContentDefaultSeeder::class);

        $response = $this->apiPost('/api/v1/proposals', $this->proposalPayload())->assertOk();
        $proposal = Proposal::query()->find($response->json('data.id'));
        $client = Client::query()->find($proposal->client_id);

        $client->update([
            'name' => 'Changed Name',
            'company' => 'Changed Co',
        ]);
        $proposal->refresh();

        $this->assertSame('Budi', $proposal->client_name);
        $this->assertSame('PT Contoh', $proposal->client_company);
    }

    public function test_missing_api_user_returns_service_unavailable(): void
    {
        $this->seed(ProposalContentDefaultSeeder::class);
        config(['document_api.user_id' => null]);

        $this->apiPost('/api/v1/proposals', $this->proposalPayload())
            ->assertStatus(503)
            ->assertJsonPath('message', 'DOCUMENT_API_USER_ID is not configured.');
    }

    public function test_standalone_invoice_dry_run(): void
    {
        $this->apiPost('/api/v1/invoices', [
            'dry_run' => true,
            'company_id' => $this->company->id,
            'client' => [
                'company' => 'PT Invoice',
                'name' => 'Ani',
            ],
            'items' => [
                ['title' => 'Website DP', 'price' => 1000000],
            ],
            'content' => [
                'additional_info' => ['mode' => 'empty'],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('dry_run', true)
            ->assertJsonPath('would_create.type', 'invoice');

        $this->assertSame(0, Invoice::query()->count());
    }

    public function test_standalone_invoice_create(): void
    {
        $response = $this->apiPost('/api/v1/invoices', [
            'company_id' => $this->company->id,
            'client' => [
                'company' => 'PT Invoice',
                'name' => 'Ani',
                'email' => 'ani@example.test',
            ],
            'tax_rate' => 11,
            'items' => [
                ['title' => 'Website DP', 'price' => 1000000, 'description' => 'First payment'],
            ],
            'content' => [
                'additional_info' => ['mode' => 'empty'],
            ],
        ])->assertOk();

        $invoice = Invoice::query()->find($response->json('data.id'));
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertSame(DocumentStatus::PUBLISHED, $invoice->status);
        $this->assertSame(PaymentStatus::UNPAID, $invoice->payment_status);
        $this->assertSame('Website DP', $invoice->getTranslation('items', 'en')[0]['title']);
        $this->assertEquals(1110000.0, (float) ($invoice->getAttributes()['total'] ?? 0));
        $this->assertNotNull($invoice->client_id);
    }

    public function test_invoice_from_proposal_copies_offer_one(): void
    {
        $this->seed(ProposalContentDefaultSeeder::class);
        $proposalId = $this->apiPost('/api/v1/proposals', $this->proposalPayload())->json('data.id');

        $response = $this->apiPost("/api/v1/proposals/{$proposalId}/invoices", [
            'dry_run' => false,
        ])->assertOk();

        $invoice = Invoice::query()->find($response->json('data.id'));
        $this->assertSame($proposalId, $invoice->proposal_id);
        $this->assertSame(PaymentStatus::UNPAID, $invoice->payment_status);
        $this->assertStringContainsString('Business Package', $invoice->getTranslation('items', 'en')[0]['title']);
        $this->assertStringContainsString('DP', $invoice->document_number);
    }

    public function test_spk_from_proposal_uses_company_pic_and_defaults(): void
    {
        $this->seed(ProposalContentDefaultSeeder::class);
        $this->seed(SpkContentDefaultSeeder::class);

        $proposalId = $this->apiPost('/api/v1/proposals', $this->proposalPayload())->json('data.id');

        $this->apiPost("/api/v1/proposals/{$proposalId}/spks", [
            'dry_run' => true,
            'company_pic_index' => 1,
            'content' => $this->spkContent(),
        ])
            ->assertOk()
            ->assertJsonPath('would_create.company_pic_name', 'Company PIC Beta');

        $this->assertSame(0, Spk::query()->count());

        $response = $this->apiPost("/api/v1/proposals/{$proposalId}/spks", [
            'company_pic_index' => 1,
            'content' => $this->spkContent(),
        ])->assertOk();

        $spk = Spk::query()->find($response->json('data.id'));
        $this->assertSame(DocumentStatus::PUBLISHED, $spk->status);
        $this->assertSame($proposalId, $spk->proposal_id);
        $this->assertSame('Company PIC Beta', $spk->company_pic_name);
        $this->assertSame('PT Contoh', $spk->client_company);
        $this->assertStringContainsString('JASA PEMBUATAN WEBSITE', $spk->getTranslation('subject', 'id'));
    }

    public function test_lookup_finds_proposals_services_and_invoices_for_a_client_company(): void
    {
        $client = Client::query()->create([
            'name' => 'Pak Yoshua',
            'company' => 'PT Rovela Karya Indonesia',
            'phone' => '0822-9805-6558',
            'notes' => [],
        ]);

        $proposal = Proposal::query()->create([
            'client_company' => 'PT Rovela Karya Indonesia',
            'client_name' => 'Pak Yoshua',
            'client_id' => $client->id,
            'company_id' => $this->company->id,
            'user_id' => $this->apiUser->id,
            'issue_date' => now()->toDateString(),
            'currency' => 'IDR',
            'status' => DocumentStatus::PUBLISHED,
            'offer_name_1' => 'Website',
            'offer_1_price' => 1000000,
        ]);

        $serviceId = DB::table('services')->insertGetId([
            'name' => 'Website hosting',
            'domain' => 'rovela.test',
            'status' => ServiceStatus::ON_GOING->value,
            'client_id' => $client->id,
            'currency' => 'IDR',
            'price' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $service = Service::query()->findOrFail($serviceId);

        $invoice = Invoice::query()->create([
            'client_company' => 'PT Rovela Karya Indonesia',
            'client_name' => 'Pak Yoshua',
            'client_id' => $client->id,
            'company_id' => $this->company->id,
            'user_id' => $this->apiUser->id,
            'proposal_id' => $proposal->id,
            'service_id' => $service->id,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => 'IDR',
            'tax_rate' => 11,
            'items' => [
                'en' => [['title' => 'DP', 'price' => 1000000, 'description' => '']],
                'id' => [['title' => 'DP', 'price' => 1000000, 'description' => '']],
            ],
            'status' => DocumentStatus::PUBLISHED,
            'payment_status' => PaymentStatus::UNPAID,
        ]);

        $this->apiGet('/api/v1/clients?q=Rovela')
            ->assertOk()
            ->assertJsonPath('data.0.id', $client->id)
            ->assertJsonPath('data.0.company', 'PT Rovela Karya Indonesia');

        $this->apiGet('/api/v1/clients/'.$client->id)
            ->assertOk()
            ->assertJsonPath('counts.proposals', 1)
            ->assertJsonPath('counts.services', 1)
            ->assertJsonPath('counts.invoices', 1)
            ->assertJsonPath('proposals.0.id', $proposal->id)
            ->assertJsonPath('services.0.name', 'Website hosting')
            ->assertJsonPath('invoices.0.service_id', $service->id);

        $this->apiGet('/api/v1/proposals?q=Rovela')
            ->assertOk()
            ->assertJsonPath('data.0.id', $proposal->id);

        $this->apiGet('/api/v1/proposals?client_id='.$client->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $proposal->id);

        $this->apiGet('/api/v1/services?client_id='.$client->id)
            ->assertOk()
            ->assertJsonPath('data.0.id', $service->id)
            ->assertJsonPath('data.0.domain', 'rovela.test');

        $this->apiGet('/api/v1/invoices?service_id='.$service->id)
            ->assertOk()
            ->assertJsonPath('data.0.id', $invoice->id)
            ->assertJsonPath('data.0.proposal_id', $proposal->id);

        $this->apiGet('/api/v1/proposals/'.$proposal->id)
            ->assertOk()
            ->assertJsonPath('data.document_number', $proposal->document_number)
            ->assertJsonPath('invoices.0.id', $invoice->id)
            ->assertJsonPath('service_ids.0', $service->id);

        $this->apiGet('/api/v1/services/'.$service->id)
            ->assertOk()
            ->assertJsonPath('data.invoices_count', 1)
            ->assertJsonPath('invoices.0.id', $invoice->id)
            ->assertJsonPath('proposal_ids.0', $proposal->id);
    }

    public function test_standalone_spk_empty_content(): void
    {
        $this->seed(SpkContentDefaultSeeder::class);

        $response = $this->apiPost('/api/v1/spks', [
            'company_id' => $this->company->id,
            'company_pic_index' => 0,
            'client' => [
                'company' => 'PT SPK',
                'name' => 'Cici',
                'pic_role' => 'Owner',
            ],
            'content' => [
                'title' => ['mode' => 'empty'],
                'subject' => ['mode' => 'empty'],
                'content' => ['mode' => 'empty'],
            ],
        ])->assertOk();

        $spk = Spk::query()->find($response->json('data.id'));
        $this->assertSame('Cici', $spk->client_pic_name);
        $this->assertSame('Owner', $spk->client_pic_role);
        $this->assertSame('Company PIC Alpha', $spk->company_pic_name);
        $this->assertSame('', $spk->getTranslation('subject', 'id'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function proposalPayload(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'client' => [
                'company' => 'PT Contoh',
                'name' => 'Budi',
                'email' => 'budi@contoh.test',
                'phone' => '08123456789',
                'address' => 'Jl. Contoh 1',
            ],
            'offer_name_1' => 'Business Package',
            'offer_1_price' => 25000000,
            'offer_1_renewal_price' => 3000000,
            'content' => $this->proposalContent(),
        ], $overrides);
    }

    /**
     * @return array<string, array{mode: string}>
     */
    private function proposalContent(): array
    {
        $content = [];

        foreach (ProposalContentCatalog::fieldKeys() as $field) {
            $content[$field] = ['mode' => 'default'];
        }

        return $content;
    }

    /**
     * @return array<string, array{mode: string}>
     */
    private function spkContent(): array
    {
        return [
            'title' => ['mode' => 'default'],
            'subject' => ['mode' => 'default'],
            'content' => ['mode' => 'default'],
        ];
    }

    private function makeCompany(): Company
    {
        return Company::query()->create([
            'company_name' => 'PT Test Agency',
            'brand_name' => 'Test Brand',
            'address' => 'Example City',
            'email_1' => 'hello@example.test',
            'phone_1' => '08123456789',
            'tax_id' => 'NPWP-001',
            'default_currency' => 'IDR',
            'color_primary' => '#111111',
            'color_secondary' => '#222222',
            'footer_text' => ['en' => 'Footer', 'id' => 'Footer'],
            'bank' => [],
            'pic' => [
                ['pic_name' => 'Company PIC Alpha', 'pic_role' => 'Director'],
                ['pic_name' => 'Company PIC Beta', 'pic_role' => 'Project Lead'],
            ],
        ]);
    }

    private function apiGet(string $uri)
    {
        return $this->withToken('test-api-key')->getJson($uri);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function apiPost(string $uri, array $payload)
    {
        return $this->withToken('test-api-key')->postJson($uri, $payload);
    }
}
