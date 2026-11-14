<?php

namespace App\Filament\Resources\Subscribers;

use App\Filament\Resources\Subscribers\Pages\ListSubscribers;
use App\Models\Subscriber;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Newsletter sign-ups. Read-only apart from deleting an address on request. */
class SubscriberResource extends Resource
{
    protected static ?string $model = Subscriber::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Inbox';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'abonnee';

    protected static ?string $pluralModelLabel = 'nieuwsbrief';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')->label('E-mail')->searchable()->copyable(),
                TextColumn::make('state')->label('Status')->state(fn (Subscriber $r) => $r->state())->badge()
                    ->color(fn (Subscriber $r) => match (true) {
                        $r->unsubscribed_at !== null => 'gray', $r->confirmed_at !== null => 'success', default => 'warning',
                    }),
                TextColumn::make('locale')->label('Taal')->formatStateUsing(fn ($state) => strtoupper($state)),
                TextColumn::make('created_at')->label('Aangemeld')->dateTime('d-m-Y H:i')->timezone('Europe/Amsterdam')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListSubscribers::route('/')];
    }
}
