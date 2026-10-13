<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class RfaFileService
{
    /**
     * Moves a freshly uploaded .rfa (stored by Filament under a random
     * name) to "{same directory}/{slug}.rfa" and returns the new path.
     * Family slugs are globally unique, so the final name is unique too.
     */
    public function renameToSlug(string $path, string $slug, string $disk = 'local'): string
    {
        $storage = Storage::disk($disk);
        $newPath = dirname($path) . '/' . $slug . '.rfa';

        if ($newPath === $path) {
            return $path;
        }

        // Anything already sitting at the target is a leftover (e.g. from an
        // interrupted upload): FamilyFile's deleting hook removes files with
        // their rows, so a live family never owns this path.
        if ($storage->exists($newPath)) {
            $storage->delete($newPath);
        }

        $storage->move($path, $newPath);

        return $newPath;
    }
}