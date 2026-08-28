<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Filament\Admin\Resources\Proposals\Actions\CreateSpkAction;
use App\Http\Middleware\DocumentAccessMiddleware;
use App\Models\Company;
use App\Models\Proposal;
use App\Models\Spk;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\SpkContentDefaultSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSpkFromProposalTest extends TestCase
{
    use RefreshDatabase;

    public function test_spk_can_be_generated_from_proposal_with_selected_company_pic(): void
    {
        Carbon::setTestNow('2026-06-25');
        $this->seed(SpkContentDefaultSeeder::class);

        $user = User::factory()->create();
        $company = Company::query()->create([
            'company_name' => 'PT Test Agency',
            'brand_name' => 'Test Brand',
            'address' => 'Example City',
            'email_1' => 'hello@example.test',
            'phone_1' => '08123456789',
            'tax_id' => 'NPWP-001',
            'default_currency' => 'IDR',
            'color_primary' => '#111111',
            'color_secondary' => '#222222',
            'footer_text' => ['en' => 'Footer', 'id' => 'Footer'],
            'bank' => [],
            'pic' => [
                [
                    'pic_name' => 'Company PIC Alpha',
                    'pic_role' => 'Director',
                ],
                [
                    'pic_name' => 'Company PIC Beta',
                    'pic_role' => 'Project Lead',
                ],
            ],
        ]);
        $proposal = Proposal::query()->create([
            'client_company' => 'PT Test Client',
            'client_name' => 'Test Client PIC',
            'client_address' => '123 Example Street',
            'client_email' => 'client@example.test',
            'client_phone' => '0800000000',
            'issue_date' => '2026-06-25',
            'valid_until' => '2026-07-25',
            'currency' => 'IDR',
            'tax_rate' => 11,
            'offer_name_1' => 'Website Plan Corporate',
            'offer_1_price' => 15000000,
            'offer_1_project_timeline' => [
                'en' => [
                    [
                        'activity_name' => 'Down Payment',
                        'activity_pic' => 'Client',
                        'activity_days' => '1',
                    ],
                    [
                        'activity_name' => 'Design',
                        'activity_pic' => 'Agency',
                        'activity_days' => '5',
                    ],
                ],
                'id' => [
                    [
                        'activity_name' => 'Pembayaran DP',
                        'activity_pic' => 'Klien',
                        'activity_days' => '1',
                    ],
                    [
                        'activity_name' => 'Proses Desain',
                        'activity_pic' => 'Agency',
                        'activity_days' => '5',
                    ],
                ],
            ],
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $spk = CreateSpkAction::createSpkFromProposal($proposal, [
            'company_pic_name' => 'Company PIC Beta',
            'company_pic_role' => 'Project Lead',
        ]);
        $secondSpk = CreateSpkAction::createSpkFromProposal($proposal, [
            'company_pic_name' => 'Company PIC Alpha',
            'company_pic_role' => 'Director',
        ]);

        $this->assertSame(DocumentStatus::PUBLISHED, $spk->status);
        $this->assertSame($proposal->id, $spk->proposal_id);
        $this->assertSame('PT Test Client', $spk->client_company);
        $this->assertSame('Test Client PIC', $spk->client_pic_name);
        $this->assertSame('Company PIC Beta', $spk->company_pic_name);
        $this->assertSame('Project Lead', $spk->company_pic_role);
        $this->assertFalse($spk->activate_translation);
        $this->assertStringStartsWith('SPK/001/VI/26/NEW', $spk->document_number);
        $this->assertStringStartsWith('SPK/002/VI/26/NEW', $secondSpk->document_number);
        $this->assertStringContainsString($proposal->document_number, $spk->getTranslation('content', 'id'));
        $this->assertStringContainsString('JASA PEMBUATAN WEBSITE', $spk->getTranslation('subject', 'id'));
        $this->assertStringContainsString('Pembayaran DP', $spk->getTranslation('content', 'id'));
        $this->assertStringContainsString('Total Hari Kerja', $spk->getTranslation('content', 'id'));
        $this->assertStringContainsString('6 hari', $spk->getTranslation('content', 'id'));
        $this->assertStringContainsString('Rp. 15.000.000', $spk->getTranslation('content', 'id'));
        $this->assertStringContainsString('Website Plan Corporate', $spk->getTranslation('content', 'id'));
        $this->assertStringContainsString('PT Test Client', $spk->getTranslation('title', 'id'));
        $this->assertStringContainsString('PERJANJIAN KERJA SAMA', $spk->getTranslation('title', 'id'));
        $this->assertStringContainsString('Test Client PIC', $spk->getTranslation('party_identification', 'id'));
        $this->assertStringContainsString('spk-party-table', $spk->getTranslation('party_identification', 'id'));
        $this->assertStringContainsString('spk-signature-table', $spk->getTranslation('signature', 'id'));
        $this->assertStringNotContainsString('Test Client PIC', $spk->getTranslation('content', 'id'));
        $this->assertStringNotContainsString('spk-party-table', $spk->getTranslation('content', 'id'));
        $this->assertStringNotContainsString('Activity', $spk->getTranslation('content', 'id'));
        $this->assertStringNotContainsString('Down Payment', $spk->getTranslation('content', 'id'));
        $this->assertStringContainsString('Down Payment', $spk->getTranslation('content', 'en'));
        $this->assertStringContainsString('Activity', $spk->getTranslation('content', 'en'));
        $this->assertStringNotContainsString('Pembayaran DP', $spk->getTranslation('content', 'en'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_spk_primary_placeholders_can_use_selected_offer_two(): void
    {
        $this->seed(SpkContentDefaultSeeder::class);

        $user = User::factory()->create();
        $company = Company::query()->create([
            'company_name' => 'PT Test Agency',
            'brand_name' => 'Test Brand',
            'address' => 'Example City',
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
            'client_company' => 'PT Test Client',
            'client_name' => 'Test Client PIC',
            'issue_date' => '2026-06-25',
            'valid_until' => '2026-07-25',
            'currency' => 'IDR',
            'tax_rate' => 11,
            'offer_name_1' => 'Website Plan Corporate',
            'offer_1_price' => 15000000,
            'offer_name_2' => 'Website Plan Prime',
            'offer_2_price' => 9000000,
            'offer_2_project_timeline' => [
                'id' => [
                    [
                        'activity_name' => 'Kickoff Prime',
                        'activity_pic' => 'Klien',
                        'activity_days' => '2',
                    ],
                ],
            ],
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $spk = CreateSpkAction::createSpkFromProposal($proposal, [
            'company_pic_name' => 'Company PIC Alpha',
            'company_pic_role' => 'Director',
            'offer_index' => 2,
        ]);

        $content = $spk->getTranslation('content', 'id');

        $this->assertStringContainsString('Website Plan Prime', $content);
        $this->assertStringContainsString('Rp. 9.000.000', $content);
        $this->assertStringContainsString('Kickoff Prime', $content);
        $this->assertStringNotContainsString('Website Plan Corporate', $content);
        $this->assertStringNotContainsString('Rp. 15.000.000', $content);
    }

    public function test_spk_frontend_renders_published_document_with_pdf_link(): void
    {
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'app.locale' => 'id',
            'app.global_access_username' => 'viewer',
            'app.global_access_password' => 'secret',
        ]);
        $this->withoutVite();

        $user = User::factory()->create();
        $company = Company::query()->create([
            'company_name' => 'PT Test Agency',
            'brand_name' => 'Test Brand',
            'address' => 'Example City',
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
        $spk = Spk::query()->create([
            'spk_date' => '2026-06-25',
            'client_company' => 'PT Test Client',
            'client_pic_name' => 'Test Client PIC',
            'client_pic_role' => 'Director',
            'client_address' => '123 Example Street',
            'company_name' => $company->company_name,
            'company_pic_name' => 'Company PIC Alpha',
            'company_pic_role' => 'Director',
            'company_address' => $company->address,
            'subject' => [
                'en' => 'Website',
                'id' => 'Website',
            ],
            'content' => [
                'en' => '<p>SPK Body</p>',
                'id' => '<p>Isi SPK</p>',
            ],
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => $user->id,
            'company_id' => $company->id,
            'notes' => [],
        ]);

        $this
            ->withSession([
                DocumentAccessMiddleware::sessionKey('spk', $spk->id) => true,
                DocumentAccessMiddleware::versionKey('spk', $spk->id) => DocumentAccessMiddleware::credentialVersion($spk),
            ])
            ->get(route('spk.show', ['slug' => $spk->slug, 'lang' => 'id']))
            ->assertOk()
            ->assertSee('PERJANJIAN KERJA SAMA', false)
            ->assertSee('PT Test Client', false)
            ->assertSee('Isi SPK', false)
            ->assertSee('Test Client PIC', false)
            ->assertSee('123 Example Street', false)
            ->assertSee('PIHAK PERTAMA', false)
            ->assertSee(route('pdf.spk', ['slug' => $spk->slug]), false)
            ->assertDontSee(route('pdf.spk', ['slug' => $spk->slug, 'lang' => 'id']), false);
    }

    public function test_spk_frontend_honors_lang_only_when_translation_is_activated(): void
    {
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'app.locale' => 'id',
            'app.global_access_username' => 'viewer',
            'app.global_access_password' => 'secret',
        ]);
        $this->withoutVite();

        $user = User::factory()->create();
        $company = Company::query()->create([
            'company_name' => 'PT Test Agency',
            'brand_name' => 'Test Brand',
            'address' => 'Example City',
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
        $spk = Spk::query()->create([
            'spk_date' => '2026-06-25',
            'client_company' => 'PT Test Client',
            'client_pic_name' => 'Test Client PIC',
            'client_pic_role' => 'Director',
            'client_address' => '123 Example Street',
            'company_name' => $company->company_name,
            'company_pic_name' => 'Company PIC Alpha',
            'company_pic_role' => 'Director',
            'company_address' => $company->address,
            'activate_translation' => false,
            'content' => [
                'en' => '<p>SPK Body</p>',
                'id' => '<p>Isi SPK</p>',
            ],
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => $user->id,
            'company_id' => $company->id,
            'notes' => [],
        ]);

        $this
            ->withSession([
                DocumentAccessMiddleware::sessionKey('spk', $spk->id) => true,
                DocumentAccessMiddleware::versionKey('spk', $spk->id) => DocumentAccessMiddleware::credentialVersion($spk),
            ])
            ->get(route('spk.show', ['slug' => $spk->slug, 'lang' => 'en']))
            ->assertOk()
            ->assertSee('Isi SPK', false)
            ->assertDontSee('SPK Body', false);

        $spk->update(['activate_translation' => true]);

        $this
            ->withSession([
                DocumentAccessMiddleware::sessionKey('spk', $spk->id) => true,
                DocumentAccessMiddleware::versionKey('spk', $spk->id) => DocumentAccessMiddleware::credentialVersion($spk),
            ])
            ->get(route('spk.show', ['slug' => $spk->slug, 'lang' => 'en']))
            ->assertOk()
            ->assertSee('SPK Body', false)
            ->assertDontSee('Isi SPK', false)
            ->assertSee(route('pdf.spk', ['slug' => $spk->slug, 'lang' => 'en']), false);
    }

    public function test_spk_frontend_renders_title_and_party_identification_overrides(): void
    {
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'app.locale' => 'id',
            'app.global_access_username' => 'viewer',
            'app.global_access_password' => 'secret',
        ]);
        $this->withoutVite();

        $user = User::factory()->create();
        $company = Company::query()->create([
            'company_name' => 'PT Test Agency',
            'brand_name' => 'Test Brand',
            'address' => 'Example City',
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
        $spk = Spk::query()->create([
            'spk_date' => '2026-06-25',
            'client_company' => 'PT Test Client',
            'client_pic_name' => 'Test Client PIC',
            'client_pic_role' => 'Director',
            'client_address' => '123 Example Street',
            'company_name' => $company->company_name,
            'company_pic_name' => 'Company PIC Alpha',
            'company_pic_role' => 'Director',
            'company_address' => $company->address,
            'title' => [
                'en' => '<p><strong>CUSTOM TITLE EN</strong></p>',
                'id' => '<p><strong>JUDUL KUSTOM</strong></p>',
            ],
            'party_identification' => [
                'en' => '<p>Custom parties EN</p>',
                'id' => '<p>Identitas pihak kustom</p>',
            ],
            'signature' => [
                'en' => '<p>Custom signature EN</p>',
                'id' => '<p>Tanda tangan kustom</p>',
            ],
            'subject' => [
                'en' => 'Website',
                'id' => 'Website',
            ],
            'content' => [
                'en' => '<p>SPK Body</p>',
                'id' => '<p>Isi SPK</p>',
            ],
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => $user->id,
            'company_id' => $company->id,
            'notes' => [],
        ]);

        $this
            ->withSession([
                DocumentAccessMiddleware::sessionKey('spk', $spk->id) => true,
                DocumentAccessMiddleware::versionKey('spk', $spk->id) => DocumentAccessMiddleware::credentialVersion($spk),
            ])
            ->get(route('spk.show', ['slug' => $spk->slug, 'lang' => 'id']))
            ->assertOk()
            ->assertSee('JUDUL KUSTOM', false)
            ->assertSee('Identitas pihak kustom', false)
            ->assertDontSee('PERJANJIAN KERJA SAMA', false)
            ->assertDontSee('Selanjutnya dalam Perjanjian ini disebut', false)
            ->assertSee('Tanda tangan kustom', false)
            ->assertSee('Isi SPK', false);
    }
}
