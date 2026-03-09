<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Proposals\Actions\CreateInvoiceAction;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class CreateInvoiceFromProposalTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_invoice_copies_additional_info_from_proposal(): void
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

        $proposal = Proposal::query()->create([
            'client_company' => 'Client Co',
            'client_name' => 'Client Name',
            'client_email' => 'client@example.test',
            'client_phone' => '0800000000',
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'currency' => 'IDR',
            'tax_rate' => 11,
            'offer_name_1' => 'Website Package',
            'offer_1_price' => 1000000,
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $proposal->setTranslations('additional_info', [
            'en' => '<p>English info</p>',
            'id' => '<p>Info Indonesia</p>',
        ]);
        $proposal->save();

        $invoice = $this->invokeCreateInvoiceFromProposal($proposal);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertSame($proposal->getTranslations('additional_info'), $invoice->getTranslations('additional_info'));
        $this->assertSame(
            [
                'id' => [[
                    'title' => 'Website Package',
                    'price' => 1000000.0,
                    'description' => '',
                ]],
                'en' => [[
                    'title' => 'Website Package',
                    'price' => 1000000.0,
                    'description' => '',
                ]],
            ],
            $invoice->items,
        );
        $this->assertSame(PaymentStatus::UNPAID, $invoice->payment_status);
        $this->assertSame($proposal->id, $invoice->proposal_id);
    }

    private function invokeCreateInvoiceFromProposal(Proposal $proposal): Invoice
    {
        $reflection = new ReflectionClass(CreateInvoiceAction::class);
        $method = $reflection->getMethod('createInvoiceFromProposal');
        $method->setAccessible(true);

        return $method->invoke(
            null,
            $proposal,
            (float) $proposal->offer_1_price,
            'Website Package',
            'DP',
        );
    }
}
