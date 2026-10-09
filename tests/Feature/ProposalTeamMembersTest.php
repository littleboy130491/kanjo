<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Http\Middleware\DocumentAccessMiddleware;
use App\Models\Company;
use App\Models\Proposal;
use App\Models\User;
use Awcodes\Curator\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProposalTeamMembersTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_copies_only_pics_flagged_for_proposals(): void
    {
        $company = $this->makeCompany([
            ['pic_name' => 'Henry', 'pic_role' => 'Director', 'pic_photo' => 7, 'show_in_proposal' => true],
            ['pic_name' => 'Finance', 'pic_role' => 'Finance Manager', 'show_in_proposal' => false],
            ['pic_name' => 'Ana', 'pic_role' => 'Designer', 'pic_photo' => null, 'show_in_proposal' => true],
        ]);

        $this->assertSame([
            ['name' => 'Henry', 'role' => 'Director', 'photo' => 7],
            ['name' => 'Ana', 'role' => 'Designer', 'photo' => null],
        ], $company->proposalTeamMembers());
    }

    public function test_team_members_stay_frozen_when_the_company_pic_changes(): void
    {
        $company = $this->makeCompany([
            ['pic_name' => 'Henry', 'pic_role' => 'Director', 'pic_photo' => 7, 'show_in_proposal' => true],
        ]);
        $proposal = $this->makeProposal($company, [
            'team_members' => $company->proposalTeamMembers(),
        ]);

        $company->update([
            'pic' => [
                ['pic_name' => 'Someone Else', 'pic_role' => 'CEO', 'pic_photo' => 9, 'show_in_proposal' => true],
            ],
        ]);

        $this->assertSame([
            ['name' => 'Henry', 'role' => 'Director', 'photo' => 7],
        ], $proposal->refresh()->team_members);
    }

    public function test_about_us_shows_team_member_photo_name_and_role_on_both_routes(): void
    {
        $this->withoutVite();

        $media = Media::query()->create([
            'disk' => 'public',
            'directory' => 'media',
            'visibility' => 'public',
            'name' => 'henry',
            'path' => 'media/henry.jpg',
            'type' => 'image',
            'ext' => 'jpg',
            'width' => 300,
            'height' => 300,
            'size' => 1000,
        ]);
        $company = $this->makeCompany();
        $proposal = $this->makeProposal($company, [
            'team_members' => [
                ['name' => 'Henry Team', 'role' => 'Director', 'photo' => $media->id],
            ],
        ]);

        foreach (['proposal-v2.show', 'proposal.show'] as $route) {
            $this
                ->withSession([
                    DocumentAccessMiddleware::sessionKey('proposal', $proposal->id) => true,
                    DocumentAccessMiddleware::versionKey('proposal', $proposal->id) => DocumentAccessMiddleware::credentialVersion($proposal),
                ])
                ->get(route($route, ['slug' => $proposal->slug]))
                ->assertOk()
                ->assertSee('id="about-us"', false)
                ->assertSee('Our Team')
                ->assertSee('Henry Team')
                ->assertSee('Director')
                ->assertSee('alt="Henry Team"', false);
        }
    }

    public function test_pdf_about_us_embeds_team_photo_as_data_uri(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('media/henry.png', 'photo-bytes');

        $media = Media::query()->create([
            'disk' => 'public',
            'directory' => 'media',
            'visibility' => 'public',
            'name' => 'henry',
            'path' => 'media/henry.png',
            'type' => 'image',
            'ext' => 'png',
            'size' => 11,
        ]);
        $proposal = $this->makeProposal($this->makeCompany(), [
            'team_members' => [
                ['name' => 'Henry Team', 'role' => 'Director', 'photo' => $media->id],
            ],
        ]);

        $html = view('proposals.show', [
            'proposal' => $proposal,
            'locale' => 'en',
            'slug' => $proposal->slug,
            'pdf' => true,
        ])->render();

        $this->assertStringContainsString('alt="Henry Team"', $html);
        $this->assertStringContainsString('src="data:', $html);
        $this->assertStringContainsString(base64_encode('photo-bytes'), $html);
    }

    public function test_about_us_section_is_hidden_without_content_or_team_members(): void
    {
        $this->withoutVite();

        $company = $this->makeCompany();
        $proposal = $this->makeProposal($company, [
            'team_members' => [],
        ]);

        $this
            ->withSession([
                DocumentAccessMiddleware::sessionKey('proposal', $proposal->id) => true,
                DocumentAccessMiddleware::versionKey('proposal', $proposal->id) => DocumentAccessMiddleware::credentialVersion($proposal),
            ])
            ->get(route('proposal-v2.show', ['slug' => $proposal->slug]))
            ->assertOk()
            ->assertDontSee('id="about-us"', false)
            ->assertDontSee('Our Team');
    }

    /**
     * @param  array<int, array<string, mixed>>  $pics
     */
    private function makeCompany(array $pics = []): Company
    {
        return Company::query()->create([
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
            'pic' => $pics,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeProposal(Company $company, array $attributes = []): Proposal
    {
        $user = User::factory()->create();

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
            'user_id' => $user->id,
            'company_id' => $company->id,
            'notes' => [],
        ], $attributes))->refresh();
    }
}
