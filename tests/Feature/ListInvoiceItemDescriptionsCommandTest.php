<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListInvoiceItemDescriptionsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_outputs_only_invoice_items_with_descriptions(): void
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

        Invoice::query()->create([
            'document_number' => 'INV/001/V/26/NEW',
            'client_company' => 'Client Co',
            'client_name' => 'Client Name',
            'client_email' => 'client@example.test',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => 'IDR',
            'tax_rate' => 11,
            'tax_amount' => 110000,
            'subtotal' => 1000000,
            'total' => 1110000,
            'items' => [
                'en' => [
                    [
                        'title' => 'Website Package',
                        'price' => 1000000,
                        'description' => '<a href="https://example.test/proposal">View proposal</a>',
                    ],
                    [
                        'title' => 'Hosting',
                        'price' => 500000,
                        'description' => '',
                    ],
                ],
            ],
            'status' => DocumentStatus::PUBLISHED,
            'payment_status' => PaymentStatus::UNPAID,
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        Invoice::query()->create([
            'document_number' => 'INV/002/V/26/NEW',
            'client_company' => 'Other Co',
            'client_name' => 'Other Name',
            'client_email' => 'other@example.test',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => 'IDR',
            'tax_rate' => 0,
            'tax_amount' => 0,
            'subtotal' => 250000,
            'total' => 250000,
            'items' => [
                'en' => [[
                    'title' => 'Support',
                    'price' => 250000,
                    'description' => '',
                ]],
            ],
            'status' => DocumentStatus::PUBLISHED,
            'payment_status' => PaymentStatus::UNPAID,
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $this->artisan('invoices:item-descriptions')
            ->expectsOutputToContain('INV/001/V/26/NEW: Website PackageView proposal')
            ->doesntExpectOutputToContain('INV/002/V/26/NEW')
            ->doesntExpectOutputToContain('Hosting')
            ->assertSuccessful();
    }

    public function test_it_reports_when_no_descriptions_exist(): void
    {
        $this->artisan('invoices:item-descriptions')
            ->expectsOutputToContain('No invoice item descriptions found.')
            ->assertSuccessful();
    }
}
