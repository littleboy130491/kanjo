<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Filament\Admin\Resources\Proposals\Actions\CreateSpkAction;
use App\Http\Middleware\DocumentAccessMiddleware;
use App\Models\Company;
use App\Models\Proposal;
use App\Models\Spk;
use App\Models\User;
use Database\Seeders\SpkContentDefaultSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSpkFromProposalTest extends TestCase
{
    use RefreshDatabase;

    public function test_spk_can_be_generated_from_proposal_with_selected_company_pic(): void
    {
        $this->seed(SpkContentDefaultSeeder::class);

        $user = User::factory()->create();
        $company = Company::query()->create([
            'company_name' => 'PT Digital Citra Kreatif',
            'brand_name' => 'Imajiner',
            'address' => 'Bogor',
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
                    'pic_name' => 'Henry Arisman',
                    'pic_role' => 'Director',
                ],
                [
                    'pic_name' => 'Jane Doe',
                    'pic_role' => 'Project Lead',
                ],
            ],
        ]);
        $proposal = Proposal::query()->create([
            'client_company' => 'PT Aquaterra Investama Aksara',
            'client_name' => 'Abyasa Kamdani',
            'client_address' => 'Menara Duta',
            'client_email' => 'client@example.test',
            'client_phone' => '0800000000',
            'issue_date' => '2026-06-25',
            'valid_until' => '2026-07-25',
            'currency' => 'IDR',
            'tax_rate' => 11,
            'offer_name_1' => 'Website Plan Corporate',
            'offer_1_price' => 15000000,
            'status' => DocumentStatus::PUBLISHED,
            'user_id' => $user->id,
            'company_id' => $company->id,
        ]);

        $spk = CreateSpkAction::createSpkFromProposal($proposal, [
            'company_pic_name' => 'Jane Doe',
            'company_pic_role' => 'Project Lead',
        ]);
        $secondSpk = CreateSpkAction::createSpkFromProposal($proposal, [
            'company_pic_name' => 'Henry Arisman',
            'company_pic_role' => 'Director',
        ]);

        $this->assertSame(DocumentStatus::PUBLISHED, $spk->status);
        $this->assertSame($proposal->id, $spk->proposal_id);
        $this->assertSame('PT Aquaterra Investama Aksara', $spk->client_company);
        $this->assertSame('Abyasa Kamdani', $spk->client_pic_name);
        $this->assertSame('Jane Doe', $spk->company_pic_name);
        $this->assertSame('Project Lead', $spk->company_pic_role);
        $this->assertStringStartsWith('SPK/001/VI/26/NEW', $spk->document_number);
        $this->assertStringStartsWith('SPK/002/VI/26/NEW', $secondSpk->document_number);
        $this->assertStringContainsString($spk->document_number, $spk->getTranslation('content', 'id'));
        $this->assertStringContainsString($proposal->document_number, $spk->getTranslation('content', 'id'));
        $this->assertStringContainsString('JASA PEMBUATAN WEBSITE', $spk->getTranslation('title', 'id'));
    }

    public function test_spk_frontend_renders_published_document_with_pdf_link(): void
    {
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'app.global_access_username' => 'viewer',
            'app.global_access_password' => 'secret',
        ]);
        $this->withoutVite();

        $user = User::factory()->create();
        $company = Company::query()->create([
            'company_name' => 'PT Digital Citra Kreatif',
            'brand_name' => 'Imajiner',
            'address' => 'Bogor',
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
            'client_company' => 'Client Co',
            'client_pic_name' => 'Client PIC',
            'client_pic_role' => 'Director',
            'client_address' => 'Client Address',
            'company_name' => $company->company_name,
            'company_pic_name' => 'Henry Arisman',
            'company_pic_role' => 'Director',
            'company_address' => $company->address,
            'title' => [
                'en' => '<p><strong>SPK Title</strong></p>',
                'id' => '<p><strong>Judul SPK</strong></p>',
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
            ->assertSee('Judul SPK', false)
            ->assertSee('Isi SPK', false)
            ->assertSee(route('pdf.spk', ['slug' => $spk->slug, 'lang' => 'id']), false);
    }
}
