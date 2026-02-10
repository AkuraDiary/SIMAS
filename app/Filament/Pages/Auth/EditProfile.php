<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Page;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Support\Components\Component;
use Filament\Tables\Columns\TextColumn;

class EditProfile extends BaseEditProfile
{

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('username')
                    ->required()
                    ->maxLength(255),
                TextInput::make('nama_lengkap')
                    ->required()
                    ->maxLength(255),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }
}
