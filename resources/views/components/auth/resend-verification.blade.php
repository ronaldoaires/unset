@props(['email' => null])

<form method="POST" action="{{ route('verification.resend') }}" {{ $attributes }}>
    @csrf

    @if ($email)
        <input type="hidden" name="email" value="{{ $email }}">
    @else
        <x-ui.input name="email" label="E-mail do cadastro" type="email" autocomplete="email" required />
    @endif

    <x-ui.button class="mt-3 w-full">Reenviar e-mail de ativação</x-ui.button>
</form>