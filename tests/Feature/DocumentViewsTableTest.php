<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Filament\Admin\Resources\DocumentViews\Pages\ListDocumentViews;
use App\Models\Company;
use App\Models\DocumentView;
use App\Models\Proposal;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentViewsTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_shows_and_searches_the_client_company(): void
    {
        config(['curator.glide_token' => 'testing-glide-token']);
        $this->seed(RoleAndPermissionSeeder::class);
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($admin);

        $company = Company::query()->create([
            'company_name' => 'PT Example Agency',
            'brand_name' => 'Example Agency',
            'address' => 'Jakarta',
            'email_1' => 'hello@example.test',
            'phone_1' => '08123456789',
            'tax_id' => 'NPWP-001',
            'default_currency' => 'IDR',
            'footer_text' => ['en' => 'Footer', 'id' => 'Footer'],
            'bank' => [],
            'pic' => [],
        ]);

        $acme = $this->viewFor($this->createProposal($company, $admin, 'PT Acme Nusantara'));
        $other = $this->viewFor($this->createProposal($company, $admin, 'CV Lain Sekali'));

        Livewire::test(ListDocumentViews::class)
            ->assertCanSeeTableRecords([$acme, $other])
            ->assertTableColumnStateSet('client_company', 'PT Acme Nusantara', $acme)
            ->searchTable('Acme')
            ->assertCanSeeTableRecords([$acme])
            ->assertCanNotSeeTableRecords([$other]);
    }

    private function createProposal(Company $company, User $user, string $clientCompany): Proposal
    {
        return Proposal::query()->create([
            'client_company' => $clientCompany,
            'client_name' => 'Rina',
            'issue_date' => '2026-08-31',
            'valid_until' => '2026-09-30',
            'currency' => 'IDR',
            'offer_name_1' => 'Website',
            'offer_1_price' => 15000000,
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => $user->id,
            'company_id' => $company->id,
            'notes' => [],
        ]);
    }

    private function viewFor(Proposal $proposal): DocumentView
    {
        return DocumentView::query()->create([
            'viewable_type' => Proposal::class,
            'viewable_id' => $proposal->id,
            'viewed_at' => now(),
            'page_type' => 'html',
            'ip_address' => '127.0.0.1',
        ]);
    }
}
