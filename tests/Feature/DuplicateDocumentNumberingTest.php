<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Invoices\Actions\DuplicateInvoiceAction;
use App\Filament\Admin\Resources\Proposals\Actions\DuplicateProposalAction;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\User;
use App\Services\DocumentNumberGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class DuplicateDocumentNumberingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_duplicated_proposal_uses_current_month_next_raw_number(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-11 09:00:00'));

        [$user, $company] = $this->createUserAndCompany();

        $this->createProposals($user, $company, '2026-05-01', 2);
        $oldProposals = $this->createProposals($user, $company, '2026-04-01', 1);

        $duplicate = DuplicateProposalAction::duplicate($oldProposals->last());

        $this->assertSame(3, $duplicate->document_number_raw);
        $this->assertSame(5, $duplicate->issue_month);
        $this->assertSame(2026, $duplicate->issue_year);
        $this->assertSame('2026-05-11', $duplicate->issue_date->toDateString());
        $this->assertSame('2026-06-10', $duplicate->valid_until->toDateString());
        $this->assertSame('QUO/003/V/26/NEW', $duplicate->document_number);
    }

    public function test_duplicated_invoice_uses_current_month_next_raw_number(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-11 09:00:00'));

        [$user, $company] = $this->createUserAndCompany();

        $this->createInvoices($user, $company, '2026-05-01', 2);
        $oldInvoices = $this->createInvoices($user, $company, '2026-04-01', 1);

        $duplicate = DuplicateInvoiceAction::duplicate($oldInvoices->last());

        $this->assertSame(3, $duplicate->document_number_raw);
        $this->assertSame(5, $duplicate->issue_month);
        $this->assertSame(2026, $duplicate->issue_year);
        $this->assertSame('2026-05-11', $duplicate->issue_date->toDateString());
        $this->assertSame('2026-06-10', $duplicate->due_date->toDateString());
        $this->assertSame('INV/003/V/26/NEW', $duplicate->document_number);
    }

    public function test_proposal_create_respects_manual_raw_number(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-11 09:00:00'));

        [$user, $company] = $this->createUserAndCompany();

        $proposal = Proposal::query()->create([
            'document_number_raw' => 7,
            'client_company' => 'Client Co',
            'client_name' => 'Client Name',
            'client_email' => 'client@example.test',
            'client_phone' => '0800000000',
            'issue_date' => '2026-05-11',
            'valid_until' => '2026-06-10',
            'currency' => 'IDR',
            'tax_rate' => 11,
            'offer_name_1' => 'Website Package',
            'offer_1_price' => 1000000,
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $this->assertSame(7, $proposal->document_number_raw);
        $this->assertSame('QUO/007/V/26/NEW', $proposal->document_number);
    }

    public function test_editing_proposal_issue_date_does_not_move_numbering_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-11 09:00:00'));

        [$user, $company] = $this->createUserAndCompany();

        $aprilProposal = Proposal::query()->create([
            'document_number_raw' => 19,
            'client_company' => 'Client Co',
            'client_name' => 'Client Name',
            'client_email' => 'client@example.test',
            'client_phone' => '0800000000',
            'issue_date' => '2026-04-19',
            'valid_until' => '2026-05-19',
            'currency' => 'IDR',
            'tax_rate' => 11,
            'offer_name_1' => 'Website Package',
            'offer_1_price' => 1000000,
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $aprilProposal->issue_date = '2026-05-11';
        $this->syncDocumentNumberForUpdate($aprilProposal);
        $this->persistDocumentNumberingState($aprilProposal);

        $mayProposal = Proposal::query()->create([
            'client_company' => 'May Client Co',
            'client_name' => 'May Client Name',
            'client_email' => 'may-client@example.test',
            'client_phone' => '0811111111',
            'issue_date' => '2026-05-11',
            'valid_until' => '2026-06-10',
            'currency' => 'IDR',
            'tax_rate' => 11,
            'offer_name_1' => 'Website Package',
            'offer_1_price' => 1000000,
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $aprilProposal->refresh();

        $this->assertSame(19, $aprilProposal->document_number_raw);
        $this->assertSame(4, $aprilProposal->issue_month);
        $this->assertSame(2026, $aprilProposal->issue_year);
        $this->assertSame('2026-05-11', $aprilProposal->issue_date->toDateString());
        $this->assertSame('QUO/019/IV/26/NEW', $aprilProposal->document_number);
        $this->assertSame(1, $mayProposal->document_number_raw);
        $this->assertSame('QUO/001/V/26/NEW', $mayProposal->document_number);
    }

    public function test_editing_invoice_issue_date_does_not_move_numbering_period(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-11 09:00:00'));

        [$user, $company] = $this->createUserAndCompany();

        $aprilInvoiceId = DB::table('invoices')->insertGetId([
            'document_number_raw' => 19,
            'document_number' => 'INV/019/IV/26/NEW',
            'document_number_override' => false,
            'issue_month' => 4,
            'issue_year' => 2026,
            'slug' => '19-1942026',
            'client_company' => 'Client Co',
            'client_name' => 'Client Name',
            'client_email' => 'client@example.test',
            'client_phone' => '0800000000',
            'issue_date' => '2026-04-19',
            'due_date' => '2026-05-19',
            'currency' => 'IDR',
            'tax_rate' => 11,
            'items' => json_encode([[
                'title' => 'Website Package',
                'price' => 1000000,
                'description' => '',
            ]]),
            'status' => DocumentStatus::PUBLISHED->value,
            'payment_status' => PaymentStatus::UNPAID->value,
            'user_id' => $user->id,
            'company_id' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $aprilInvoice = Invoice::query()->findOrFail($aprilInvoiceId);
        $aprilInvoice->issue_date = '2026-05-11';
        $this->syncDocumentNumberForUpdate($aprilInvoice);
        $this->persistDocumentNumberingState($aprilInvoice);

        $mayInvoiceNumber = DocumentNumberGenerator::generate('INV', Carbon::parse('2026-05-11'));

        $aprilInvoice->refresh();

        $this->assertSame(19, $aprilInvoice->document_number_raw);
        $this->assertSame(4, $aprilInvoice->issue_month);
        $this->assertSame(2026, $aprilInvoice->issue_year);
        $this->assertSame('2026-05-11', $aprilInvoice->issue_date->toDateString());
        $this->assertSame('INV/019/IV/26/NEW', $aprilInvoice->document_number);
        $this->assertSame(1, $mayInvoiceNumber['document_number_raw']);
        $this->assertSame('INV/001/V/26/NEW', $mayInvoiceNumber['document_number']);
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function createUserAndCompany(): array
    {
        $user = User::factory()->create();
        $company = Company::query()->create([
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
            'bank' => [],
            'pic' => [],
        ]);

        return [$user, $company];
    }

    private function createProposals(User $user, Company $company, string $issueDate, int $count)
    {
        return collect(range(1, $count))->map(fn(int $index) => Proposal::query()->create([
            'client_company' => 'Client Co',
            'client_name' => 'Client Name',
            'client_email' => 'client@example.test',
            'client_phone' => '0800000000',
            'issue_date' => $issueDate,
            'valid_until' => Carbon::parse($issueDate)->addDays(30)->toDateString(),
            'currency' => 'IDR',
            'tax_rate' => 11,
            'offer_name_1' => 'Website Package ' . $index,
            'offer_1_price' => 1000000,
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]));
    }

    private function createInvoices(User $user, Company $company, string $issueDate, int $count)
    {
        return collect(range(1, $count))->map(fn(int $index) => Invoice::query()->create([
            'client_company' => 'Client Co',
            'client_name' => 'Client Name',
            'client_email' => 'client@example.test',
            'client_phone' => '0800000000',
            'issue_date' => $issueDate,
            'due_date' => Carbon::parse($issueDate)->addDays(30)->toDateString(),
            'currency' => 'IDR',
            'tax_rate' => 11,
            'items' => [[
                'title' => 'Website Package ' . $index,
                'price' => 1000000,
                'description' => '',
            ]],
            'status' => DocumentStatus::PUBLISHED,
            'payment_status' => PaymentStatus::UNPAID,
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]));
    }

    private function syncDocumentNumberForUpdate(Model $model): void
    {
        $method = new ReflectionMethod($model, 'syncDocumentNumberForUpdate');
        $method->setAccessible(true);
        $method->invoke(null, $model);
    }

    private function persistDocumentNumberingState(Model $model): void
    {
        DB::table($model->getTable())
            ->where('id', $model->getKey())
            ->update([
                'document_number' => $model->document_number,
                'document_number_raw' => $model->document_number_raw,
                'issue_month' => $model->issue_month,
                'issue_year' => $model->issue_year,
                'issue_date' => $model->issue_date->toDateString(),
            ]);
    }
}
