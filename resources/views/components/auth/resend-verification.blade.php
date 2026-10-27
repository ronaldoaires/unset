@props(['email' => null])

<form method="POST" action="{{ route('verification.resend') }}" {{ $attributes }}>
    @csrf
    <x-auth.bot-trap />

    @if ($email)
        <input type="hidden" name="email" value="{{ $email }}">

        {{-- The e-mail field is hidden here, so its errors are shown on their own. --}}
        @error('email')
            <p class="mb-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    @else
        <x-ui.input name="email" label="E-mail do cadastro" type="email" autocomplete="email" required />
    @endif

    <x-ui.button class="mt-3 w-full">Reenviar e-mail de ativação</x-ui.button>
</form>