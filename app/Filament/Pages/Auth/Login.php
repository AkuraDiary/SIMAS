<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'username' => $data['login'],
            'password' => $data['password'],
        ];
    }

    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Username')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => __('Credential Mismatch'),
        ]);
    }

    // public function authenticate(): ?LoginResponse
    // {
    //     try {
    //         $data = $this->form->getState();
            
    //         // DEBUG: See what data is being sent
    //         // dd($data); 
    
    //         if (! Auth::attempt([
    //             'username' => $data['login'],
    //             'password' => $data['password'],
    //         ], $data['remember'] ?? false)) {
    //             $this->throwFailureValidationException();
    //         }
    
    //         session()->regenerate();
    //         return parent::authenticate();
    //         // return app(\Filament\Http\Responses\Auth\LoginResponse::class);
    //     } catch (\Exception $e) {
    //         // This will tell you if there is a database error (e.g., column not found)
    //         // dd($e->getMessage());
    //     }
    // }

    // public function authenticate(): ?\Filament\Http\Responses\Auth\Contracts\LoginResponse
    // {
    //     try {
    //         return parent::authenticate();
    //     } catch (\Exception $e) {
    //         // This will dump the error to your screen so you can see the technical reason
    //         dd($e->getMessage());
    //     }
    // }
}
