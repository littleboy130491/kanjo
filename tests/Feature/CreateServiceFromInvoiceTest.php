<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Filament\Admin\Resources\Invoices\Support\InvoiceServiceSupport;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateServiceFromInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_price_defaults_to_linked_proposal_offer_1_renewal_price(): void
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
        $client = Client::query()->create([
            'name' => 'Client Name',
            'company' => 'Client Co',
            'email' => 'client@example.test',
            'phone' => '0800000000',
        ]);
        $proposal = Proposal::query()->create([
            'client_company' => $client->company,
            'client_name' => $client->name,
            'client_email' => $client->email,
            'client_phone' => $client->phone,
            'issue_date' => '2026-05-07',
            'valid_until' => '2026-06-06',
            'currency' => 'IDR',
            'tax_rate' => 11,
            'offer_name_1' => 'Website Package',
            'offer_1_price' => 1000000,
            'offer_1_renewal_price' => 2500000,
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => $user->id,
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);
        $invoice = Invoice::query()->create([
            'client_company' => $client->company,
            'client_name' => $client->name,
            'client_email' => $client->email,
            'client_phone' => $client->phone,
            'issue_date' => '2026-05-07',
            'due_date' => '2026-06-06',
            'currency' => 'IDR',
            'tax_rate' => 11,
            'items' => [[
                'title' => 'Initial build',
                'price' => 1000000,
                'description' => '',
            ]],
            'status' => DocumentStatus::PUBLISHED,
            'payment_status' => PaymentStatus::UNPAID,
            'proposal_id' => $proposal->id,
            'user_id' => $user->id,
            'company_id' => $company->id,
            'client_id' => $client->id,
        ]);

        $service = InvoiceServiceSupport::createServiceFromInvoice($invoice, [
            'name' => 'Website Renewal',
            'domain' => 'example.test',
            'currency' => 'IDR',
            'start_date' => '2026-05-07',
            'renewal_date' => '2027-05-07',
        ]);

        $this->assertSame('2500000.00', $service->price);
        $this->assertSame($client->id, $service->client_id);
    }
}
