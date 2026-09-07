<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNegativeAmountTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_totals_allow_negative_item_prices(): void
    {
        [$user, $company] = $this->makeIssuer();

        $invoice = Invoice::query()->create([
            'client_company' => 'Client Co',
            'client_name' => 'Client Name',
            'client_email' => 'client@example.test',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => 'IDR',
            'tax_rate' => 11,
            'items' => [
                'en' => [
                    [
                        'title' => 'Website Package',
                        'price' => 1000000,
                        'description' => '',
                    ],
                    [
                        'title' => 'Credit',
                        'price' => -250000,
                        'description' => 'Discount',
                    ],
                ],
            ],
            'status' => DocumentStatus::PUBLISHED,
            'payment_status' => PaymentStatus::UNPAID,
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $this->assertSame(750000.0, (float) $invoice->getAttributes()['subtotal']);
        $this->assertSame(82500.0, (float) $invoice->getAttributes()['tax_amount']);
        $this->assertSame(832500.0, (float) $invoice->getAttributes()['total']);
    }

    public function test_invoice_can_be_entirely_negative(): void
    {
        [$user, $company] = $this->makeIssuer();

        $invoice = Invoice::query()->create([
            'client_company' => 'Client Co',
            'client_name' => 'Client Name',
            'client_email' => 'client@example.test',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => 'IDR',
            'tax_rate' => 11,
            'items' => [
                'en' => [[
                    'title' => 'Refund',
                    'price' => -150000,
                    'description' => '',
                ]],
            ],
            'status' => DocumentStatus::PUBLISHED,
            'payment_status' => PaymentStatus::UNPAID,
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $this->assertSame(-150000.0, (float) $invoice->getAttributes()['subtotal']);
        $this->assertSame(-16500.0, (float) $invoice->getAttributes()['tax_amount']);
        $this->assertSame(-166500.0, (float) $invoice->getAttributes()['total']);
        $this->assertSame('{"v":-166500}', json_encode(['v' => (float) $invoice->getAttributes()['total']]));
    }

    public function test_tiny_negative_totals_are_stored_as_unsigned_zero(): void
    {
        [$user, $company] = $this->makeIssuer();

        $invoice = Invoice::query()->create([
            'client_company' => 'Client Co',
            'client_name' => 'Client Name',
            'client_email' => 'client@example.test',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => 'IDR',
            'tax_rate' => 0,
            'items' => [
                'en' => [[
                    'title' => 'Rounding',
                    'price' => -0.004,
                    'description' => '',
                ]],
            ],
            'status' => DocumentStatus::PUBLISHED,
            'payment_status' => PaymentStatus::UNPAID,
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $this->assertSame(0.0, (float) $invoice->getAttributes()['subtotal']);
        $this->assertSame('{"v":0}', json_encode(['v' => (float) $invoice->getAttributes()['subtotal']]));
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function makeIssuer(): array
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
}
