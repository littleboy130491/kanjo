<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Filament\Admin\Resources\Proposals\Actions\CreateInvoiceAction;
use App\Http\Middleware\DocumentAccessMiddleware;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class CompanyTaxNoticeTest extends TestCase
{
    use RefreshDatabase;

    private const NOTICE_EN = 'We are a non-VAT-registered company (Non-PKP).';

    private const NOTICE_ID = 'Perusahaan kami merupakan Pengusaha Non-PKP.';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'app.global_access_username' => 'viewer',
            'app.global_access_password' => 'secret',
        ]);
        $this->withoutVite();
    }

    public function test_invoice_shows_tax_notice_in_selected_language(): void
    {
        $proposal = $this->createProposal($this->createCompany(withNotice: true));
        $invoice = $this->createInvoiceFromProposal($proposal);
        $invoice->update(['activate_translation' => true]);

        $this->withDocumentSession('invoice', $invoice)
            ->get(route('invoice.show', ['slug' => $invoice->slug]))
            ->assertOk()
            ->assertSeeInOrder(['Payment Info', self::NOTICE_EN]);

        $this->withDocumentSession('invoice', $invoice)
            ->get(route('invoice.show', ['slug' => $invoice->slug, 'lang' => 'id']))
            ->assertOk()
            ->assertSee(self::NOTICE_ID);
    }

    public function test_invoice_freezes_tax_notice_at_creation(): void
    {
        $company = $this->createCompany(withNotice: true);
        $invoice = $this->createInvoiceFromProposal($this->createProposal($company));

        $this->assertSame(
            ['en' => self::NOTICE_EN, 'id' => self::NOTICE_ID],
            $invoice->fresh()->getTranslations('tax_notice'),
        );

        $company->update(['tax_notice' => ['en' => 'Changed later.', 'id' => 'Diubah kemudian.']]);

        $this->assertSame(self::NOTICE_EN, $invoice->fresh()->getTranslation('tax_notice', 'en'));

        $this->withDocumentSession('invoice', $invoice)
            ->get(route('invoice.show', ['slug' => $invoice->slug]))
            ->assertOk()
            ->assertSee(self::NOTICE_EN)
            ->assertDontSee('Changed later.');
    }

    public function test_invoice_keeps_its_own_tax_notice_on_create(): void
    {
        $company = $this->createCompany(withNotice: true);

        $invoice = Invoice::query()->create([
            'client_company' => 'Client Co',
            'client_name' => 'Client Name',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => 'IDR',
            'items' => ['en' => [['title' => 'Support', 'price' => 100000, 'description' => '']]],
            'tax_notice' => ['en' => 'Custom notice.'],
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => User::factory()->create()->id,
            'company_id' => $company->id,
        ]);

        $this->assertSame(['en' => 'Custom notice.'], $invoice->fresh()->getTranslations('tax_notice'));
    }

    public function test_tax_notice_is_hidden_when_empty(): void
    {
        $proposal = $this->createProposal($this->createCompany(withNotice: false));
        $invoice = $this->createInvoiceFromProposal($proposal);

        $this->withDocumentSession('invoice', $invoice)
            ->get(route('invoice.show', ['slug' => $invoice->slug]))
            ->assertOk()
            ->assertDontSee(self::NOTICE_EN);
    }

    private function withDocumentSession(string $type, Invoice $document): static
    {
        return $this->withSession([
            DocumentAccessMiddleware::sessionKey($type, $document->id) => true,
            DocumentAccessMiddleware::versionKey($type, $document->id) => DocumentAccessMiddleware::credentialVersion($document),
        ]);
    }

    private function createCompany(bool $withNotice): Company
    {
        return Company::query()->create([
            'company_name' => 'PT Example Agency',
            'brand_name' => 'Example',
            'address' => 'Jakarta',
            'email_1' => 'hello@example.test',
            'phone_1' => '08123456789',
            'tax_id' => 'NPWP-001',
            'default_currency' => 'IDR',
            'color_primary' => '#111111',
            'color_secondary' => '#222222',
            'footer_text' => ['en' => 'Footer', 'id' => 'Footer'],
            'tax_notice' => $withNotice ? ['en' => self::NOTICE_EN, 'id' => self::NOTICE_ID] : null,
            'bank' => [],
            'pic' => [],
        ]);
    }

    private function createProposal(Company $company): Proposal
    {
        return Proposal::query()->create([
            'client_company' => 'Client Co',
            'client_name' => 'Client Name',
            'client_email' => 'client@example.test',
            'client_phone' => '0800000000',
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'currency' => 'IDR',
            'offer_name_1' => 'Website Package',
            'offer_1_price' => 1000000,
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => User::factory()->create()->id,
            'company_id' => $company->id,
        ]);
    }

    private function createInvoiceFromProposal(Proposal $proposal): Invoice
    {
        $method = (new ReflectionClass(CreateInvoiceAction::class))->getMethod('createInvoiceFromProposal');
        $method->setAccessible(true);

        $invoice = $method->invoke(null, $proposal, 1000000.0, 'Website Package', 'DP');
        $invoice->update(['status' => DocumentStatus::PUBLISHED]);

        return $invoice;
    }
}
