<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getLoginFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getRememberFormComponent(),
        ])
            ->statePath('data');
    }
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }

    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function throwFailureValidationException(): never
    {
        // 1. Send the Notification
        Notification::make()
            ->title(__('auth.failed'))
            ->danger()
            ->send();

        // 2. Throw an empty exception to stop the process without field errors
        // throw ValidationException::withMessages([]);
        throw ValidationException::withMessages([
            'data.username' => __('auth.failed'),
            'data.password' => __('auth.failed'),
        ]);
    }
}
