<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Http\Middleware\DocumentAccessMiddleware;
use App\Models\Company;
use App\Models\Proposal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProposalV2ViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_v2_route_renders_the_new_full_width_proposal_view(): void
    {
        $this->withoutVite();

        $proposal = $this->createPublishedProposal();

        $this
            ->withSession([
                DocumentAccessMiddleware::sessionKey('proposal', $proposal->id) => true,
                DocumentAccessMiddleware::versionKey('proposal', $proposal->id) => DocumentAccessMiddleware::credentialVersion($proposal),
            ])
            ->get(route('proposal-v2.show', ['slug' => $proposal->slug]))
            ->assertOk()
            ->assertViewIs('proposals.show-v2')
            ->assertSee('proposal-v2', false)
            ->assertSee('PT Example Client', false);
    }

    public function test_v2_authentication_returns_the_reader_to_the_v2_route(): void
    {
        config([
            'app.global_access_username' => 'viewer',
            'app.global_access_password' => 'secret',
        ]);
        $this->withoutVite();

        $proposal = $this->createPublishedProposal();

        $this
            ->get(route('proposal-v2.show', ['slug' => $proposal->slug]))
            ->assertOk()
            ->assertSee(route('proposal-v2.auth', ['slug' => $proposal->slug]), false);

        $this
            ->post(route('proposal-v2.auth', ['slug' => $proposal->slug]), [
                'username' => 'viewer',
                'password' => 'secret',
            ])
            ->assertRedirect(route('proposal-v2.show', ['slug' => $proposal->slug]));
    }

    private function createPublishedProposal(): Proposal
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test Admin',
            'email' => 'admin@example.test',
            'password' => 'not-used-by-this-test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $company = Company::query()->create([
            'company_name' => 'PT Example Agency',
            'brand_name' => 'Example Agency',
            'address' => 'Jakarta',
            'email_1' => 'hello@example.test',
            'phone_1' => '08123456789',
            'tax_id' => 'NPWP-001',
            'default_currency' => 'IDR',
            'color_primary' => '#164e63',
            'color_secondary' => '#0f766e',
            'footer_text' => ['en' => 'Example footer', 'id' => 'Contoh footer'],
            'bank' => [],
            'pic' => [],
        ]);

        $proposal = Proposal::query()->create([
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
            'user_id' => $userId,
            'company_id' => $company->id,
            'notes' => [],
        ]);

        return $proposal->refresh();
    }
}
