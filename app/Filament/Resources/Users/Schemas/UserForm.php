<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('phone')
                    ->label('Telefone')
                    ->tel()
                    // Dynamic mask: 10 digits (landline) or 11 digits (mobile).
                    ->mask(RawJs::make(<<<'JS'
                        $input.replace(/\D/g, '').length > 10 ? '(99) 99999-9999' : '(99) 9999-9999'
                    JS))
                    ->placeholder('(66) 99999-9999')
                    ->maxLength(20),

                Select::make('role')
                    ->label('Perfil')
                    ->options([
                        User::ROLE_USER       => 'Usuário',
                        User::ROLE_ADMIN      => 'Moderador',
                        User::ROLE_SUPERADMIN => 'Superadmin',
                    ])
                    ->default(User::ROLE_USER)
                    ->required()
                    ->native(false)
                    // A disabled field is not dehydrated, so the role stays untouched.
                    // Prevents a superadmin from demoting themselves.
                    ->disabled(fn(?User $record) => $record?->is(auth()->user()))
                    ->helperText(fn(?User $record) => $record?->is(auth()->user())
                        ? 'Você não pode alterar o seu próprio perfil.'
                        : null),

                TextInput::make('password')
                    ->label('Senha')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(fn(string $operation) => $operation === 'create')
                    // Empty on edit means "keep the current password". Hashing is
                    // handled by the "hashed" cast on the User model.
                    ->dehydrated(fn(?string $state) => filled($state))
                    ->helperText(fn(string $operation) => $operation === 'edit'
                        ? 'Deixe vazio para manter a senha atual.'
                        : null),

                Toggle::make('is_active')
                    ->label('Conta ativa')
                    ->default(true)
                    ->inline(false)
                    ->disabled(fn(?User $record) => $record?->is(auth()->user())),
            ]);
    }
}
