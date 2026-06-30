<?php

namespace App\Filament\Support;

use App\Support\RichTextHtmlNormalizer;
use Awcodes\Curator\Components\Forms\RichEditor\AttachCuratorMediaPlugin;
use Filament\Forms\Components\RichEditor;

class RichEditorHtml
{
    public static function configure(RichEditor $editor): RichEditor
    {
        return $editor
            ->enableToolbarButtons(['attachCuratorMedia'])
            ->plugins([AttachCuratorMediaPlugin::make()])
            ->afterStateHydrated(function (RichEditor $component): void {
                self::normalizeRawState($component);
            });
    }

    public static function normalizeRawState(RichEditor $component): void
    {
        $rawState = $component->getRawState();

        if (is_array($rawState)) {
            return;
        }

        if (is_string($rawState)) {
            $rawState = RichTextHtmlNormalizer::normalize($rawState);
        }

        foreach ($component->getStateCasts() as $stateCast) {
            $rawState = $stateCast->set($rawState);
        }

        $component->rawState($rawState);
    }
}
