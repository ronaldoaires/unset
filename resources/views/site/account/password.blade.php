<x-layouts.account title="Senha">
    <x-account.page-header
        title="Senha"
        :description="$hasPassword
            ? 'Troque sua senha regularmente para manter a conta segura.'
            : 'Sua conta entra pelo Google. Crie uma senha se quiser também entrar com e-mail e senha.'" />

    <form method="POST" action="{{ route('account.password.update') }}" class="space-y-6 rounded-card bg-surface p-6 shadow-card">
        @csrf
        @method('PUT')

        @if ($hasPassword)
        <x-ui.input name="current_password" label="Senha atual" type="password" autocomplete="current-password" required />
        @endif

        <x-ui.input
            name="password"
            :label="$hasPassword ? 'Nova senha' : 'Senha'"
            type="password"
            autocomplete="new-password"
            hint="Mínimo de 8 caracteres, com letras e números."
            required />
        <x-ui.input name="password_confirmation" label="Confirmar senha" type="password" autocomplete="new-password" required />

        <x-ui.button class="w-full sm:w-auto">{{ $hasPassword ? 'Alterar senha' : 'Criar senha' }}</x-ui.button>
    </form>
</x-layouts.account>