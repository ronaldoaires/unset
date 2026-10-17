<?php

namespace App\Http\Controllers\Site\Revit;

use App\Http\Controllers\Controller;
use App\Models\Family;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FamilyFavoriteController extends Controller
{
    public function store(Request $request, Family $family): JsonResponse|RedirectResponse
    {
        abort_unless($family->isPublished(), 404);

        try {
            $request->user()->favorites()->firstOrCreate(['family_id' => $family->id]);
        } catch (UniqueConstraintViolationException) {
            // A parallel request (double click) saved it first: same end result.
        }

        return $this->respond($request, true, 'Família adicionada aos favoritos.');
    }

    public function destroy(Request $request, Family $family): JsonResponse|RedirectResponse
    {
        // No published check on purpose: users can always clean up their list.
        $request->user()->favorites()->where('family_id', $family->id)->delete();

        return $this->respond($request, false, 'Família removida dos favoritos.');
    }

    /**
     * Heart link shown to guests. Logged-in users never get here through the UI.
     */
    public function prompt(Request $request, Family $family): RedirectResponse
    {
        abort_unless($family->isPublished(), 404);

        if ($request->user()) {
            return redirect()->route('revit.families.show', ['family' => $family->slug]);
        }

        // guest() stores this URL as the "intended" one: after the login the
        // person lands here again and is sent on to the family page.
        return redirect()->guest(route('login'));
    }

    /**
     * JSON for the fetch() call of the heart button, a redirect back otherwise
     * (the button also works with JavaScript turned off).
     */
    private function respond(Request $request, bool $favorited, string $message): JsonResponse|RedirectResponse
    {
        return $request->expectsJson()
            ? response()->json(['favorited' => $favorited])
            : back()->with('status', $message);
    }
}