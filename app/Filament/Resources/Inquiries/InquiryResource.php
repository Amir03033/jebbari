<?php

namespace App\Filament\Resources\Inquiries;

use App\Enums\InquiryStatus;
use App\Filament\Resources\Inquiries\Pages\ListInquiries;
use App\Filament\Resources\Inquiries\Pages\ViewInquiry;
use App\Filament\Resources\Inquiries\RelationManagers\NotesRelationManager;
use App\Models\Inquiry;
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

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static string|\UnitEnum|null $navigationGroup = 'Inbox';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'aanvraag';

    protected static ?string $pluralModelLabel = 'aanvragen';

    public static function getNavigationBadge(): ?string
    {
        $unread = Inquiry::query()->whereNull('read_at')->count();

        return $unread ?: null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Aanvraag')->columns(['default' => 1, 'md' => 2])->schema([
                TextEntry::make('name')->label('Naam'),
                TextEntry::make('email')->label('E-mail')->copyable()->url(fn (Inquiry $r) => 'mailto:'.$r->email),
                TextEntry::make('company')->label('Bedrijf')->placeholder('—'),
                TextEntry::make('budget')->label('Budget')->placeholder('—'),
                TextEntry::make('created_at')->label('Ontvangen')->dateTime('d-m-Y H:i')->timezone('Europe/Amsterdam'),
                TextEntry::make('locale')->label('Taal')->formatStateUsing(fn ($state) => strtoupper($state)),
                TextEntry::make('message')->label('Bericht')->columnSpanFull()->extraAttributes(['style' => 'white-space: pre-wrap']),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Naam')->searchable()
                    ->weight(fn (Inquiry $r) => $r->isUnread() ? 'bold' : 'normal')
                    ->description(fn (Inquiry $r) => $r->email),
                TextColumn::make('company')->label('Bedrijf')->placeholder('—')->toggleable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (InquiryStatus $state) => $state->label())
                    ->color(fn (InquiryStatus $state) => match ($state) {
                        InquiryStatus::New => 'warning', InquiryStatus::InProgress => 'info', InquiryStatus::Completed => 'success', InquiryStatus::Spam => 'gray',
                    }),
                TextColumn::make('read_at')->label('Gelezen')->state(fn (Inquiry $r) => $r->isUnread() ? 'Ongelezen' : 'Gelezen')->badge()
                    ->color(fn (Inquiry $r) => $r->isUnread() ? 'warning' : 'gray'),
                TextColumn::make('created_at')->label('Ontvangen')->dateTime('d-m-Y H:i')->timezone('Europe/Amsterdam')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Status')->options(collect(InquiryStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getRelations(): array
    {
        return [NotesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInquiries::route('/'),
            'view' => ViewInquiry::route('/{record}'),
        ];
    }
}
