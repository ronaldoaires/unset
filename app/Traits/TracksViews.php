<?php

namespace App\Traits;

trait TracksViews
{
    /**
     * Laravel calls this automatically when the model boots, so the
     * models using the trait don't need to declare these casts.
     */
    public function initializeTracksViews(): void
    {
        $this->mergeCasts([
            'view_count'     => 'integer',
            'last_viewed_at' => 'datetime',
        ]);
    }

    /**
     * Registers one visit: a single atomic UPDATE that bumps the counter
     * and the last-visit date, without touching updated_at.
     */
    public function incrementViews(): void
    {
        $now = now();

        // toBase() bypasses Eloquent's automatic updated_at handling, so a
        // visit is never recorded as an edit of the record. The increment
        // and the last-visit date go out in one atomic UPDATE.
        $this->newQuery()
            ->whereKey($this->getKey())
            ->toBase()
            ->increment('view_count', 1, ['last_viewed_at' => $now]);

        // Keep the in-memory instance consistent with the database.
        $this->view_count     = ($this->view_count ?? 0) + 1;
        $this->last_viewed_at = $now;
        $this->syncOriginalAttributes(['view_count', 'last_viewed_at']);
    }
}
