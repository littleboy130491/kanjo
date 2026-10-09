<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Companies\CompanyResource;
use App\Filament\Admin\Resources\Companies\Pages\CreateCompany;
use App\Filament\Admin\Resources\Proposals\Pages\CreateProposal;
use App\Filament\Admin\Resources\Proposals\ProposalResource;
use Awcodes\Curator\Components\Forms\CuratorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuratorPhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_uploads_are_stored_without_cropping_or_resizing(): void
    {
        $pickers = $this->photoPickers();

        $this->assertSame(['logo', 'pic_sign', 'pic_photo', 'photo'], array_keys($pickers));

        foreach ($pickers as $name => $picker) {
            $this->assertNull($picker->getImageCropAspectRatio(), "{$name} crops on upload.");
            $this->assertNull($picker->getImageResizeMode(), "{$name} resizes on upload.");
            $this->assertNull($picker->getImageResizeTargetWidth(), "{$name} resizes on upload.");
            $this->assertNull($picker->getImageResizeTargetHeight(), "{$name} resizes on upload.");
        }
    }

    public function test_photo_previews_show_the_whole_image(): void
    {
        foreach ($this->photoPickers() as $name => $picker) {
            $this->assertTrue($picker->isConstrained(), "{$name} preview is cropped.");
        }
    }

    /** @return array<string, CuratorPicker> */
    private function photoPickers(): array
    {
        $pickers = [];
        $walk = function (array $components) use (&$pickers, &$walk): void {
            foreach ($components as $component) {
                if ($component instanceof CuratorPicker) {
                    $pickers[$component->getName()] = $component;
                }

                if ($component instanceof Repeater) {
                    $walk($component->getChildComponents());
                }
            }
        };

        $walk(CompanyResource::form(Schema::make(app(CreateCompany::class)))->getFlatComponents(withActions: false, withHidden: true));
        $walk(ProposalResource::form(Schema::make(app(CreateProposal::class)))->getFlatComponents(withActions: false, withHidden: true));

        return $pickers;
    }
}
