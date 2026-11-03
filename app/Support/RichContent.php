<?php

namespace App\Support;

use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\HtmlString;

/**
 * Renders HTML saved by the Filament RichEditor for the public site.
 * Going through Filament's renderer (instead of echoing the raw HTML)
 * re-parses the content against the editor's own schema, so only the
 * tags and attributes the editor knows about reach the page.
 */
class RichContent
{
    public static function render(?string $html): ?HtmlString
    {
        if (blank($html)) {
            return null;
        }

        return new HtmlString(
            RichContentRenderer::make($html)
                ->fileAttachmentsDisk('public')
                ->fileAttachmentsVisibility('public')
                ->toHtml()
        );
    }
}
