<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Filament\Admin\Resources\Proposals\Actions\DuplicateProposalAction;
use App\Filament\Admin\Resources\Proposals\Pages\CreateProposal;
use App\Http\Middleware\DocumentAccessMiddleware;
use App\Models\Company;
use App\Models\Proposal;
use App\Models\User;
use Awcodes\Curator\Models\Media;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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

    public function test_create_form_prefills_team_from_flagged_company_pics_and_saves_the_snapshot(): void
    {
        config(['curator.glide_token' => 'testing-glide-token']);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->actingAs(User::query()->where('email', 'admin@example.com')->firstOrFail());

        $media = Media::query()->create([
            'disk' => 'public',
            'directory' => 'media',
            'visibility' => 'public',
            'name' => 'henry',
            'path' => 'media/henry.jpg',
            'type' => 'image',
            'ext' => 'jpg',
            'size' => 1000,
        ]);
        $this->makeCompany([
            ['pic_name' => 'Henry', 'pic_role' => 'Director', 'pic_photo' => $media->id, 'show_in_proposal' => true],
            ['pic_name' => 'Finance', 'pic_role' => 'Finance Manager', 'show_in_proposal' => false],
        ]);

        Livewire::test(CreateProposal::class)
            ->fillForm([
                'client_company' => 'PT Example Client',
                'client_name' => 'Rina',
                'offer_name_1' => 'Website Design & Development',
                'offer_1_price' => 15000000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame([
            ['name' => 'Henry', 'role' => 'Director', 'photo' => $media->id],
        ], Proposal::query()->sole()->team_members);
    }

    public function test_changing_company_on_create_form_replaces_the_prefilled_team(): void
    {
        config(['curator.glide_token' => 'testing-glide-token']);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->actingAs(User::query()->where('email', 'admin@example.com')->firstOrFail());

        $this->makeCompany([
            ['pic_name' => 'Henry', 'pic_role' => 'Director', 'show_in_proposal' => true],
        ]);
        $otherCompany = Company::query()->create([
            'company_name' => 'PT Other Agency',
            'brand_name' => 'Other Agency',
            'address' => 'Bandung',
            'email_1' => 'other@example.test',
            'phone_1' => '08123456780',
            'tax_id' => 'NPWP-002',
            'default_currency' => 'IDR',
            'color_primary' => '#111111',
            'color_secondary' => '#222222',
            'footer_text' => ['en' => 'Footer', 'id' => 'Footer'],
            'bank' => [],
            'pic' => [
                ['pic_name' => 'Ana', 'pic_role' => 'Designer', 'show_in_proposal' => true],
            ],
        ]);

        Livewire::test(CreateProposal::class)
            ->fillForm([
                'company_id' => $otherCompany->id,
                'client_company' => 'PT Example Client',
                'client_name' => 'Rina',
                'offer_name_1' => 'Website Design & Development',
                'offer_1_price' => 15000000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame([
            ['name' => 'Ana', 'role' => 'Designer', 'photo' => null],
        ], Proposal::query()->sole()->team_members);
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

    public function test_team_members_are_shown_by_default(): void
    {
        $this->withoutVite();

        $proposal = $this->makeProposal($this->makeCompany(), [
            'team_members' => [
                ['name' => 'Henry Team', 'role' => 'Director', 'photo' => null],
            ],
        ]);

        $this->assertTrue($proposal->show_team_member);

        $this
            ->withSession([
                DocumentAccessMiddleware::sessionKey('proposal', $proposal->id) => true,
                DocumentAccessMiddleware::versionKey('proposal', $proposal->id) => DocumentAccessMiddleware::credentialVersion($proposal),
            ])
            ->get(route('proposal.show', ['slug' => $proposal->slug]))
            ->assertOk()
            ->assertSee('Our Team')
            ->assertSee('Henry Team');
    }

    public function test_hidden_team_members_are_not_rendered_but_about_us_stays(): void
    {
        $this->withoutVite();

        $proposal = $this->makeProposal($this->makeCompany(), [
            'about_us' => ['en' => '<p>About copy</p>', 'id' => '<p>Tentang kami</p>'],
            'team_members' => [
                ['name' => 'Henry Team', 'role' => 'Director', 'photo' => null],
            ],
            'show_team_member' => false,
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
                ->assertSee('About copy')
                ->assertDontSee('Our Team')
                ->assertDontSee('Henry Team');
        }
    }

    public function test_about_us_is_hidden_when_only_hidden_team_members_remain(): void
    {
        $this->withoutVite();

        $proposal = $this->makeProposal($this->makeCompany(), [
            'team_members' => [
                ['name' => 'Henry Team', 'role' => 'Director', 'photo' => null],
            ],
            'show_team_member' => false,
        ]);

        $this
            ->withSession([
                DocumentAccessMiddleware::sessionKey('proposal', $proposal->id) => true,
                DocumentAccessMiddleware::versionKey('proposal', $proposal->id) => DocumentAccessMiddleware::credentialVersion($proposal),
            ])
            ->get(route('proposal.show', ['slug' => $proposal->slug]))
            ->assertOk()
            ->assertDontSee('id="about-us"', false)
            ->assertDontSee('Our Team');
    }

    public function test_duplicate_keeps_the_show_team_member_flag(): void
    {
        $proposal = $this->makeProposal($this->makeCompany(), [
            'team_members' => [
                ['name' => 'Henry Team', 'role' => 'Director', 'photo' => null],
            ],
            'show_team_member' => false,
        ]);

        $duplicate = DuplicateProposalAction::duplicate($proposal);

        $this->assertFalse($duplicate->refresh()->show_team_member);
        $this->assertSame($proposal->team_members, $duplicate->team_members);
    }

    public function test_create_form_saves_the_show_team_member_toggle(): void
    {
        config(['curator.glide_token' => 'testing-glide-token']);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->actingAs(User::query()->where('email', 'admin@example.com')->firstOrFail());

        $this->makeCompany([
            ['pic_name' => 'Henry', 'pic_role' => 'Director', 'show_in_proposal' => true],
        ]);

        Livewire::test(CreateProposal::class)
            ->fillForm([
                'client_company' => 'PT Example Client',
                'client_name' => 'Rina',
                'offer_name_1' => 'Website Design & Development',
                'offer_1_price' => 15000000,
                'show_team_member' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertFalse(Proposal::query()->sole()->show_team_member);
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
