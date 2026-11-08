<?php

namespace App\Filament\Resources\Activity;

use App\Filament\Resources\Activity\Pages\ListActivities;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

/** Read-only audit log over Spatie Activitylog. */
class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Systeem';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'logboek';

    protected static ?string $modelLabel = 'logregel';

    protected static ?string $pluralModelLabel = 'logboek';

    private const EVENTS = [
        'created' => 'Aangemaakt', 'updated' => 'Gewijzigd', 'deleted' => 'Verwijderd', 'draft_saved' => 'Concept opgeslagen',
        'published' => 'Gepubliceerd', 'published_changes' => 'Wijzigingen gepubliceerd', 'unpublished' => 'Offline gehaald',
        'draft_discarded' => 'Concept verworpen', 'login' => 'Ingelogd', 'login_failed' => 'Mislukte inlogpoging',
    ];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Wijziging')->columns(2)->schema([
                TextEntry::make('created_at')->label('Wanneer')->dateTime('d-m-Y H:i:s')->timezone('Europe/Amsterdam'),
                TextEntry::make('causer.name')->label('Door')->placeholder('Systeem'),
                TextEntry::make('event')->label('Gebeurtenis')->formatStateUsing(fn ($state) => self::EVENTS[$state] ?? $state),
                TextEntry::make('subject_type')->label('Onderdeel')->formatStateUsing(fn ($state, $record) => $state ? class_basename($state).' #'.$record->subject_id : '—'),
                TextEntry::make('properties')->label('Details')->columnSpanFull()->html()
                    ->formatStateUsing(fn ($state) => '<pre style="white-space: pre-wrap; font-size: .8rem; margin: 0">'.e(json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)).'</pre>'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Wanneer')->dateTime('d-m-Y H:i')->timezone('Europe/Amsterdam')->sortable(),
                TextColumn::make('event')->label('Gebeurtenis')->badge()->formatStateUsing(fn ($state) => self::EVENTS[$state] ?? $state),
                TextColumn::make('subject_type')->label('Onderdeel')->formatStateUsing(fn ($state, $record) => $state ? class_basename($state).' #'.$record->subject_id : '—'),
                TextColumn::make('causer.name')->label('Door')->placeholder('Systeem'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([SelectFilter::make('event')->label('Gebeurtenis')->options(self::EVENTS)])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListActivities::route('/')];
    }
}
