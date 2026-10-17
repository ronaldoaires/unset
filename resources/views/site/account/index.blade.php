<x-layouts.account title="Minha conta">
    <x-account.page-header
        :title="'Olá, ' . \Illuminate\Support\Str::before($user->name, ' ')"
        description="Gerencie seus dados e acompanhe sua conta." />

    <div class="rounded-card bg-surface p-6 shadow-card">
        <dl class="space-y-4 text-sm">
            <div>
                <dt class="text-subtle">E-mail</dt>
                <dd class="mt-0.5 font-medium text-ink">{{ $user->email }}</dd>
            </div>

            <div>
                <dt class="text-subtle">Plano</dt>
                <dd class="mt-0.5 font-medium text-ink">{{ $user->currentPlan()?->name ?? 'Sem plano ativo' }}</dd>
            </div>
        </dl>
    </div>
</x-layouts.account>