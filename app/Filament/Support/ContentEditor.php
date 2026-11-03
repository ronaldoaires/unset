<?php

namespace App\Filament\Support;

use App\Services\ImageService;
use Filament\Forms\Components\RichEditor;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Rich text field shared by services and portfolio projects: images go to
 * the public disk as webp, in a "content" folder per model, so
 * CleansRichContentImages can find and remove them later. Images can be
 * resized in the editor by dragging their corners.
 */
class ContentEditor
{
    public static function make(string $name, string $directory): RichEditor
    {
        return RichEditor::make($name)
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'link'],
                ['h2', 'h3'],
                ['bulletList', 'orderedList', 'blockquote'],
                ['attachFiles', 'horizontalRule'],
                ['undo', 'redo'],
            ])
            // Click an image and drag its corners to resize it. The width is saved
            // in the HTML, so the public page shows the same size (capped at 100%).
            ->resizableImages()
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory($directory)
            ->fileAttachmentsVisibility('public')
            ->fileAttachmentsAcceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
            ->fileAttachmentsMaxSize(5120) // 5 MB, in KB
            ->saveUploadedFileAttachmentUsing(
                fn (TemporaryUploadedFile $file) => app(ImageService::class)->storeContentImage($file, $directory)
            )
            ->columnSpanFull();
    }
}
