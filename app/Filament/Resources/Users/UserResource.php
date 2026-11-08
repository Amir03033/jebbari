<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Systeem';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'gebruiker';

    protected static ?string $pluralModelLabel = 'gebruikers';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Account')->columns(['default' => 1, 'md' => 2])->schema([
                TextInput::make('name')->label('Naam')->required()->maxLength(120),
                TextInput::make('email')->label('E-mail')->email()->required()->unique(ignoreRecord: true),
                Select::make('role')->label('Rol')->required()
                    ->options(collect(UserRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])),
                Toggle::make('is_active')->label('Actief')->default(true),
                TextInput::make('password')->label('Wachtwoord')->password()->revealable()
                    ->rule(Password::min(12))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->helperText('Bij het eerste inloggen wordt gevraagd een authenticator-app te koppelen. Leeg laten om niets te wijzigen.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Naam')->weight('medium')->searchable()->description(fn (User $r) => $r->email),
                TextColumn::make('role')->label('Rol')->badge()->formatStateUsing(fn (UserRole $state) => $state->label()),
                IconColumn::make('mfa')->label('Authenticator')->boolean()->state(fn (User $r) => filled($r->app_authentication_secret)),
                IconColumn::make('is_active')->label('Actief')->boolean(),
                TextColumn::make('last_login_at')->label('Laatst ingelogd')->dateTime('d-m-Y H:i')->timezone('Europe/Amsterdam')->placeholder('Nog niet'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
