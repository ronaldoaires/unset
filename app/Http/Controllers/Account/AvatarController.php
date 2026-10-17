<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\ImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AvatarController extends Controller
{
    public function edit(Request $request): View
    {
        return view('site.account.avatar', ['user' => $request->user()]);
    }

    public function update(Request $request, ImageService $images): RedirectResponse
    {
        // SVG is not accepted by the "image" rule, and the file is re-encoded
        // to webp anyway, so only a real raster image ever gets stored.
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
        ], [
            'avatar.required' => 'Escolha uma imagem.',
            'avatar.image'    => 'O arquivo precisa ser uma imagem.',
            'avatar.mimes'    => 'Use uma imagem JPG, PNG ou WebP.',
            'avatar.max'      => 'A imagem pode ter no máximo 1 MB.',
            'avatar.uploaded' => 'Não foi possível enviar a imagem. Tente uma menor (até 1 MB).',
        ]);

        $user = $request->user();

        $path = $images->storeSquare(
            file: $request->file('avatar'),
            baseName: (string) $user->id,
            oldPath: $user->hasUploadedAvatar() ? $user->avatar : null,
        );

        $user->update(['avatar' => $path]);

        return redirect()->route('account.avatar')->with('status', 'Foto atualizada.');
    }

    public function destroy(Request $request, ImageService $images): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasUploadedAvatar()) {
            $images->deleteImageAndThumb($user->avatar, 'public');
        }

        $user->update(['avatar' => null]);

        return redirect()->route('account.avatar')->with('status', 'Foto removida.');
    }
}