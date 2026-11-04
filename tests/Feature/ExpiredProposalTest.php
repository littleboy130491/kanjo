<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpiredProposalTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo('2026-11-04 09:00:00');
        $this->seed(RoleAndPermissionSeeder::class);
        $this->admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->company = Company::query()->create([
            'company_name' => 'PT Example Agency',
            'brand_name' => 'Example Agency',
            'address' => 'Jakarta',
            'email_1' => 'hello@example.test',
            'phone_1' => '08123456789',
            'website' => 'example.test',
            'tax_id' => 'NPWP-001',
            'default_currency' => 'IDR',
            'color_primary' => '#164e63',
            'color_secondary' => '#0f766e',
            'footer_text' => ['en' => 'Example footer', 'id' => 'Contoh footer'],
            'bank' => [],
            'pic' => [],
        ]);
    }

    public function test_command_expires_published_proposals_past_valid_until(): void
    {
        $past = $this->createProposal(['valid_until' => '2026-11-03']);
        $today = $this->createProposal(['valid_until' => '2026-11-04']);
        $draft = $this->createProposal(['valid_until' => '2026-11-03', 'status' => DocumentStatus::DRAFT]);
        $paid = $this->createProposal(['valid_until' => '2026-11-03']);
        $this->createInvoice($paid, PaymentStatus::PAID);

        $this->artisan('documents:check-overdue')->assertSuccessful();
        $this->artisan('documents:check-overdue')->assertSuccessful();

        $this->assertSame(DocumentStatus::EXPIRED, $past->refresh()->status);
        $this->assertSame(DocumentStatus::PUBLISHED, $today->refresh()->status);
        $this->assertSame(DocumentStatus::DRAFT, $draft->refresh()->status);
        $this->assertSame(DocumentStatus::PUBLISHED, $paid->refresh()->status);
    }

    public function test_public_visitors_see_the_expired_page_without_the_access_gate(): void
    {
        config(['app.global_access_username' => 'viewer', 'app.global_access_password' => 'secret']);
        $proposal = $this->createProposal(['status' => DocumentStatus::EXPIRED]);

        foreach (['proposal.show', 'proposal-v2.show', 'pdf.proposal'] as $route) {
            $this->get(route($route, ['slug' => $proposal->slug]))
                ->assertStatus(410)
                ->assertViewIs('proposals.expired')
                ->assertSee('This proposal has expired.')
                ->assertSee('Please contact us if you need any help')
                ->assertSee('hello@example.test')
                ->assertSee($proposal->document_number)
                ->assertDontSee('PT Example Client')
                ->assertDontSee('Website Design &amp; Development', false)
                ->assertDontSee('name="password"', false);
        }
    }

    public function test_expired_page_follows_the_selected_language(): void
    {
        $proposal = $this->createProposal(['status' => DocumentStatus::EXPIRED, 'activate_translation' => true]);

        $this->get(route('proposal.show', ['slug' => $proposal->slug, 'lang' => 'id']))
            ->assertStatus(410)
            ->assertSee('Proposal ini telah kedaluwarsa.')
            ->assertSee('Silakan hubungi kami apabila membutuhkan bantuan');
    }

    public function test_chat_is_not_available_on_expired_proposals(): void
    {
        $proposal = $this->createProposal(['status' => DocumentStatus::EXPIRED]);

        $this->postJson(route('proposal.chat', ['slug' => $proposal->slug]), ['message' => 'Hi'])
            ->assertNotFound();
    }

    public function test_admins_still_open_expired_proposals_with_a_notice(): void
    {
        $proposal = $this->createProposal(['status' => DocumentStatus::EXPIRED]);

        $this->actingAs($this->admin)
            ->get(route('proposal.show', ['slug' => $proposal->slug]))
            ->assertOk()
            ->assertViewIs('proposals.show')
            ->assertSee('PT Example Client')
            ->assertSee('This proposal has EXPIRED (valid until 30 Sep 2026)');
    }

    public function test_published_proposals_show_no_expired_notice(): void
    {
        $proposal = $this->createProposal();

        $this->actingAs($this->admin)
            ->get(route('proposal.show', ['slug' => $proposal->slug]))
            ->assertOk()
            ->assertDontSee('This proposal has EXPIRED');
    }

    public function test_invoices_cannot_be_set_to_expired_through_the_status_options(): void
    {
        $this->assertSame(
            ['draft' => 'Draft', 'published' => 'Published'],
            DocumentStatus::options(DocumentStatus::invoiceCases()),
        );
        $this->assertArrayHasKey('expired', DocumentStatus::options());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createProposal(array $attributes = []): Proposal
    {
        return Proposal::query()->create(array_merge([
            'client_company' => 'PT Example Client',
            'client_name' => 'Rina',
            'client_email' => 'rina@example.test',
            'client_phone' => '081298765432',
            'issue_date' => '2026-08-31',
            'valid_until' => '2026-09-30',
            'currency' => 'IDR',
            'brief' => ['en' => '<p>Project brief</p>', 'id' => '<p>Ringkasan proyek</p>'],
            'offer_name_1' => 'Website Design & Development',
            'offer_1_price' => 15000000,
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => $this->admin->id,
            'company_id' => $this->company->id,
            'notes' => [],
        ], $attributes))->refresh();
    }

    private function createInvoice(Proposal $proposal, PaymentStatus $paymentStatus): Invoice
    {
        return Invoice::query()->create([
            'client_company' => $proposal->client_company,
            'client_name' => $proposal->client_name,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-31',
            'currency' => 'IDR',
            'tax_rate' => 11,
            'items' => [['title' => 'Initial build', 'price' => 1000000, 'description' => '']],
            'status' => DocumentStatus::PUBLISHED,
            'payment_status' => $paymentStatus,
            'proposal_id' => $proposal->id,
            'user_id' => $this->admin->id,
            'company_id' => $this->company->id,
        ]);
    }
}
