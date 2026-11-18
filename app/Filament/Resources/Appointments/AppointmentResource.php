<?php

namespace App\Filament\Resources\Appointments;

use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\Leads\LeadResource;
use App\Leads\Booking;
use App\Models\Appointment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Booked meetings. Cancelling here mails the visitor, like their own cancel link. */
class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Inbox';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'afspraak';

    protected static ?string $pluralModelLabel = 'afspraken';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Appointment::query()->confirmed()->where('starts_at', '>', now())->where('starts_at', '<', now()->addDays(7))->count();

        return $count ? (string) $count : null;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('lead', 'type');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('starts_at')->label('Wanneer')->dateTime('D d-m-Y H:i')->timezone('Europe/Amsterdam')->sortable(),
                TextColumn::make('type.name.nl')->label('Gesprek'),
                TextColumn::make('lead.name')->label('Met')->description(fn (Appointment $r) => $r->lead?->email)
                    ->url(fn (Appointment $r) => $r->lead ? LeadResource::getUrl('view', ['record' => $r->lead]) : null),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn ($state) => $state === Appointment::CONFIRMED ? 'Bevestigd' : 'Geannuleerd')
                    ->color(fn ($state) => $state === Appointment::CONFIRMED ? 'success' : 'gray'),
                TextColumn::make('message')->label('Onderwerp')->limit(60)->placeholder('—')->toggleable(),
            ])
            ->defaultSort('starts_at')
            ->filters([
                Filter::make('upcoming')->label('Alleen komende')->default()->query(fn (Builder $q) => $q->where('starts_at', '>=', now()->startOfDay())),
                SelectFilter::make('status')->label('Status')->options([Appointment::CONFIRMED => 'Bevestigd', Appointment::CANCELLED => 'Geannuleerd']),
            ])
            ->recordActions([
                Action::make('cancel')->label('Annuleren')->icon('heroicon-o-x-circle')->color('danger')
                    ->visible(fn (Appointment $r) => $r->isUpcoming())
                    ->requiresConfirmation()->modalDescription('De bezoeker krijgt hiervan een e-mail.')
                    ->action(fn (Appointment $r) => app(Booking::class)->cancel($r->load('lead', 'type'))),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAppointments::route('/')];
    }
}
