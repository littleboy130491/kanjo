<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Mcp\Servers\KanjoServer;
use App\Mcp\Tools\CreateInvoiceFromProposalTool;
use App\Mcp\Tools\CreateProposalTool;
use App\Mcp\Tools\GetRecordTool;
use App\Mcp\Tools\ReadGuideTool;
use App\Mcp\Tools\SearchRecordsTool;
use App\Mcp\Tools\UpdateRecordTool;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\User;
use App\Services\DocumentApi\ProposalContentCatalog;
use Database\Seeders\ProposalContentDefaultSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ProposalContentDefaultSeeder::class);

        $this->editor = User::factory()->create();
        $this->editor->assignRole(Role::findByName(UserRole::Editor->value, 'web'));
        $this->company = $this->makeCompany();

        // MCP documents are authored by the connected user, so the shared API author is not needed.
        config(['document_api.user_id' => null]);
    }

    public function test_unauthenticated_requests_get_an_oauth_challenge(): void
    {
        $this->postJson('/mcp', $this->rpc('tools/list'))
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer realm="mcp", resource_metadata="'.url('/.well-known/oauth-protected-resource/mcp').'"');

        $this->getJson('/.well-known/oauth-protected-resource/mcp')
            ->assertOk()
            ->assertJsonPath('resource', url('/mcp'))
            ->assertJsonPath('scopes_supported', ['mcp:use']);

        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('authorization_endpoint', route('passport.authorizations.authorize'))
            ->assertJsonPath('registration_endpoint', url('/oauth/register'));
    }

    public function test_client_registration_only_accepts_allowed_redirect_domains(): void
    {
        $this->postJson('/oauth/register', [
            'client_name' => 'Claude',
            'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'],
        ])->assertCreated()->assertJsonPath('scope', 'mcp:use');

        $this->postJson('/oauth/register', [
            'client_name' => 'Local MCP client',
            'redirect_uris' => ['http://localhost:6274/oauth/callback'],
        ])->assertCreated();

        $this->postJson('/oauth/register', [
            'client_name' => 'Unknown',
            'redirect_uris' => ['https://evil.example/callback'],
        ])->assertStatus(400)->assertJsonPath('error', 'invalid_redirect_uri');
    }

    public function test_full_oauth_flow_connects_an_mcp_client(): void
    {
        $this->useFreshPassportKeys();
        $this->withoutVite();

        $clientId = $this->postJson('/oauth/register', [
            'client_name' => 'Claude',
            'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'],
        ])->assertCreated()->json('client_id');

        $verifier = str_repeat('v', 64);
        $authorizeUrl = '/oauth/authorize?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
            'response_type' => 'code',
            'scope' => 'mcp:use',
            'state' => 'state-123',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ]);

        $this->get($authorizeUrl)->assertRedirect(route('filament.admin.auth.login'));

        $consent = $this->actingAs($this->editor)->get($authorizeUrl)
            ->assertOk()
            ->assertSee('Connect Claude to')
            ->assertSee('Authorize');
        preg_match('/name="auth_token" value="([^"]+)"/', $consent->getContent(), $matches);

        $callback = $this->post(route('passport.authorizations.approve'), [
            'state' => '',
            'client_id' => $clientId,
            'auth_token' => $matches[1],
        ])->assertRedirect();
        parse_str((string) parse_url($callback->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('state-123', $query['state']);

        $accessToken = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
            'code_verifier' => $verifier,
            'code' => $query['code'],
        ])->assertOk()->json('access_token');

        $this->app['auth']->forgetGuards();

        $this->withToken($accessToken)
            ->postJson('/mcp', $this->rpc('tools/call', [
                'name' => 'search_records',
                'arguments' => ['type' => 'company'],
            ]))
            ->assertOk()
            ->assertJsonPath('result.isError', false)
            ->assertJsonPath('result.structuredContent.data.0.company_name', 'PT Test Agency');
    }

    public function test_consent_screen_blocks_users_without_panel_access(): void
    {
        $this->useFreshPassportKeys();
        $this->withoutVite();

        $clientId = $this->postJson('/oauth/register', [
            'client_name' => 'Claude',
            'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'],
        ])->json('client_id');

        $this->actingAs(User::factory()->create())
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
                'response_type' => 'code',
                'scope' => 'mcp:use',
                'code_challenge' => str_repeat('c', 43),
                'code_challenge_method' => 'S256',
            ]))
            ->assertOk()
            ->assertSee('does not have admin panel access')
            ->assertDontSee('>Authorize</button>', false);
    }

    public function test_users_without_panel_access_are_rejected(): void
    {
        Passport::actingAs(User::factory()->create(), ['mcp:use']);

        $this->postJson('/mcp', $this->rpc('tools/list'))->assertForbidden();
    }

    public function test_editor_creates_a_proposal_over_http_as_themselves(): void
    {
        Passport::actingAs($this->editor, ['mcp:use']);

        $tools = $this->postJson('/mcp', $this->rpc('tools/list'))->assertOk()->json('result.tools');
        $this->assertEqualsCanonicalizing([
            'read_guide',
            'search_records',
            'get_record',
            'get_content_defaults',
            'get_skeleton',
            'create_proposal',
            'create_invoice',
            'create_spk',
            'create_invoice_from_proposal',
            'create_spk_from_proposal',
            'update_record',
        ], array_column($tools, 'name'));

        $response = $this->postJson('/mcp', $this->rpc('tools/call', [
            'name' => 'create_proposal',
            'arguments' => ['payload' => $this->proposalPayload()],
        ]))->assertOk();

        $this->assertFalse($response->json('result.isError'));
        $proposal = Proposal::query()->findOrFail($response->json('result.structuredContent.data.id'));
        $this->assertSame($this->editor->id, $proposal->user_id);
        $this->assertSame('published', $proposal->status->value);
        $this->assertSame('PT Contoh', $proposal->client_company);
        $this->assertStringContainsString('/proposal/', $response->json('result.structuredContent.data.public_url'));
    }

    public function test_dry_run_validates_without_writing(): void
    {
        KanjoServer::actingAs($this->editor)
            ->tool(CreateProposalTool::class, ['payload' => $this->proposalPayload(), 'dry_run' => true])
            ->assertHasNoErrors()
            ->assertStructuredContent(fn ($json) => $json->where('valid', true)->etc());

        $this->assertSame(0, Proposal::query()->count());
    }

    public function test_api_validation_errors_are_returned_to_the_agent(): void
    {
        $payload = $this->proposalPayload();
        unset($payload['content']['brief']);

        KanjoServer::actingAs($this->editor)
            ->tool(CreateProposalTool::class, ['payload' => $payload])
            ->assertHasErrors(['missing_content_fields', 'brief']);

        $this->assertSame(0, Proposal::query()->count());
    }

    public function test_editor_can_look_up_companies_needed_for_creates(): void
    {
        KanjoServer::actingAs($this->editor)
            ->tool(SearchRecordsTool::class, ['type' => 'company'])
            ->assertHasNoErrors()
            ->assertSee('PT Test Agency');
    }

    public function test_writes_follow_panel_permissions(): void
    {
        KanjoServer::actingAs($this->editor)
            ->tool(UpdateRecordTool::class, [
                'type' => 'company',
                'id' => $this->company->id,
                'payload' => ['brand_name' => 'Renamed'],
            ])
            ->assertHasErrors(['not allowed to update company']);

        $this->assertSame('Test Brand', $this->company->refresh()->brand_name);
    }

    public function test_invoice_from_proposal_and_lookup(): void
    {
        KanjoServer::actingAs($this->editor)
            ->tool(CreateProposalTool::class, ['payload' => $this->proposalPayload()])
            ->assertHasNoErrors();
        $proposal = Proposal::query()->sole();

        KanjoServer::actingAs($this->editor)
            ->tool(CreateInvoiceFromProposalTool::class, ['proposal_id' => $proposal->id, 'payload' => []])
            ->assertHasNoErrors();

        $invoice = Invoice::query()->sole();
        $this->assertSame($proposal->id, $invoice->proposal_id);
        $this->assertSame($this->editor->id, $invoice->user_id);

        KanjoServer::actingAs($this->editor)
            ->tool(GetRecordTool::class, ['type' => 'proposal', 'id' => $proposal->id])
            ->assertHasNoErrors()
            ->assertSee($invoice->document_number);

        KanjoServer::actingAs($this->editor)
            ->tool(GetRecordTool::class, ['type' => 'invoice', 'id' => 999999])
            ->assertHasErrors(['No invoice with id 999999']);
    }

    public function test_guide_explains_tool_mapping(): void
    {
        KanjoServer::actingAs($this->editor)
            ->tool(ReadGuideTool::class)
            ->assertSee(['create_invoice_from_proposal', 'Kanjo Document API']);
    }

    private function useFreshPassportKeys(): void
    {
        $key = @openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);

        if ($key === false) {
            $this->markTestSkipped('This PHP build cannot generate RSA keys, so the OAuth flow cannot run.');
        }

        openssl_pkey_export($key, $privateKey);

        config([
            'passport.private_key' => $privateKey,
            'passport.public_key' => openssl_pkey_get_details($key)['key'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function rpc(string $method, array $params = []): array
    {
        return ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params];
    }

    /**
     * @return array<string, mixed>
     */
    private function proposalPayload(): array
    {
        $content = [];

        foreach (ProposalContentCatalog::fieldKeys() as $field) {
            $content[$field] = ['mode' => 'default'];
        }

        return [
            'company_id' => $this->company->id,
            'client' => [
                'company' => 'PT Contoh',
                'name' => 'Budi',
                'email' => 'budi@contoh.test',
            ],
            'offer_name_1' => 'Business Package',
            'offer_1_price' => 25000000,
            'offer_1_renewal_price' => 3000000,
            'content' => $content,
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
            ],
        ]);
    }
}
