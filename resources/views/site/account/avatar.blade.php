<x-layouts.account title="Avatar">
    <x-account.page-header
        title="Avatar"
        description="Personalize sua foto de perfil." />

    <div class="space-y-6 rounded-card bg-surface p-6 shadow-card">
        <div class="flex items-center gap-5">
            <x-ui.avatar :user="$user" preview-id="avatar-preview" class="size-24 text-3xl" />

            <p class="text-sm text-muted">
                A imagem é recortada em formato quadrado. Use JPG, PNG ou WebP de até 1 MB.
            </p>
        </div>

        <form method="POST" action="{{ route('account.avatar.update') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label for="avatar" class="mb-1.5 block text-sm font-medium text-ink">Nova foto</label>

                <input
                    id="avatar"
                    name="avatar"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    data-image-preview="avatar-preview"
                    data-max-kb="1024"
                    data-error-target="avatar-client-error"
                    required
                    class="block w-full rounded-control border border-line bg-surface text-sm text-muted file:mr-4 file:min-h-12 file:cursor-pointer file:border-0 file:bg-primary-soft file:px-5 file:text-sm file:font-semibold file:text-on-primary-soft hover:file:bg-primary-soft-hover">

                @error('avatar')
                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p id="avatar-client-error" role="alert" class="mt-1.5 hidden text-sm text-red-600"></p>
            </div>

            <x-ui.button class="w-full sm:w-auto">Salvar foto</x-ui.button>
        </form>

        @if ($user->avatar)
        <form method="POST" action="{{ route('account.avatar.destroy') }}" class="border-t border-line pt-6">
            @csrf
            @method('DELETE')
            <x-ui.button variant="outline" class="w-full sm:w-auto">Remover foto</x-ui.button>
        </form>
        @endif
    </div>
</x-layouts.account>