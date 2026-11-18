<?php

namespace App\Filament\Resources\Proposals;

use App\Filament\Resources\Proposals\Pages\CreateProposal;
use App\Filament\Resources\Proposals\Pages\EditProposal;
use App\Filament\Resources\Proposals\Pages\ListProposals;
use App\Leads\PriceCalculator;
use App\Models\Lead;
use App\Models\Proposal;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Personal proposals: made here, shared as a private link, accepted or
 * declined by the client online (docs/PROPOSALS.md). Owner only. Locked once
 * the client has decided.
 */
class ProposalResource extends Resource
{
    protected static ?string $model = Proposal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Inbox';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'voorstel';

    protected static ?string $pluralModelLabel = 'voorstellen';

    public static function canAccess(): bool
    {
        return auth()->user()?->isOwner() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $locked = fn (?Proposal $record) => $record?->isDecided() ?? false;

        return $schema->columns(1)->components([
            Callout::make(fn (?Proposal $record) => $record?->accepted_at ? 'De klant heeft akkoord gegeven' : 'De klant heeft dit voorstel afgewezen')
                ->description(fn (?Proposal $record) => $record?->accepted_at
                    ? "Akkoord door {$record->accepted_name} op ".$record->accepted_at->timezone(config('site.timezone'))->format('d-m-Y H:i').'. Het voorstel kan niet meer worden gewijzigd.'
                    : ($record?->decline_reason ? 'Reden: '.$record->decline_reason : 'Geen reden opgegeven.'))
                ->color(fn (?Proposal $record) => $record?->accepted_at ? 'success' : 'danger')
                ->visible($locked)
                ->columnSpanFull(),

            Section::make('Klant')->columns(['default' => 1, 'md' => 2])->disabled($locked)->schema([
                Select::make('lead_id')->label('Hoort bij lead (optioneel)')
                    ->options(fn () => Lead::query()->reachable()->latest()->limit(200)->get()->mapWithKeys(fn (Lead $l) => [$l->id => trim(($l->name ?: $l->email).($l->company ? " ({$l->company})" : ''))]))
                    ->searchable()->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($lead = Lead::find($state)) {
                            $set('client_name', $lead->name);
                            $set('client_email', $lead->email);
                            $set('client_company', $lead->company);
                            $set('locale', $lead->locale);
                        }
                    })
                    ->helperText('Vult naam, e-mail en taal in. Bij akkoord of afwijzing wordt de status van de lead bijgewerkt.'),
                Select::make('locale')->label('Taal van het voorstel')->options(['nl' => 'Nederlands', 'en' => 'Engels'])->default('nl')->required(),
                TextInput::make('client_name')->label('Naam klant')->required()->maxLength(120),
                TextInput::make('client_email')->label('E-mail klant')->email()->required()->maxLength(160),
                TextInput::make('client_company')->label('Bedrijf (optioneel)')->maxLength(120),
                DatePicker::make('valid_until')->label('Geldig tot (optioneel)')->helperText('Daarna kan de klant geen akkoord meer geven.'),
            ]),

            Section::make('Voorstel')->disabled($locked)->schema([
                TextInput::make('title')->label('Titel')->required()->maxLength(140)->placeholder('Bijvoorbeeld: Nieuwe website voor Bakkerij De Vries'),
                Textarea::make('intro')->label('Toelichting')->rows(5)->maxLength(5000)
                    ->helperText('Wat je hebt begrepen en wat je voorstelt. Een witregel begint een nieuwe alinea.'),
            ]),

            Section::make('Wat je bouwt')->disabled($locked)->schema([
                Repeater::make('scope')->hiddenLabel()->addActionLabel('Onderdeel toevoegen')->reorderable()->collapsible()
                    ->itemLabel(fn (array $state) => $state['title'] ?? null)
                    ->schema([
                        TextInput::make('title')->label('Onderdeel')->required()->maxLength(120),
                        Textarea::make('description')->label('Uitleg')->rows(2)->maxLength(600),
                    ]),
            ]),

            Section::make('Planning')->disabled($locked)->schema([
                Repeater::make('timeline')->hiddenLabel()->addActionLabel('Fase toevoegen')->reorderable()->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextInput::make('phase')->label('Fase')->required()->maxLength(120),
                        TextInput::make('duration')->label('Duur of datum')->maxLength(80)->placeholder('Bijvoorbeeld: week 1 tot 2'),
                    ]),
            ]),

            Section::make('Investering')->disabled($locked)->description('Hele euro\'s. Totalen worden per soort (eenmalig, per maand, per jaar) berekend.')->schema([
                Repeater::make('prices')->hiddenLabel()->addActionLabel('Prijsregel toevoegen')->reorderable()->columns(['default' => 1, 'md' => 4])
                    ->schema([
                        TextInput::make('description')->label('Omschrijving')->required()->maxLength(160)->columnSpan(['md' => 2]),
                        TextInput::make('amount')->label('Bedrag (€)')->numeric()->integer()->minValue(0)->maxValue(10000000)->required(),
                        Select::make('period')->label('Soort')->options(Proposal::PERIODS)->default('once')->required(),
                    ]),
            ]),

            Section::make('Voorwaarden')->collapsed()->disabled($locked)->schema([
                Textarea::make('terms')->label('Voorwaarden en afspraken (optioneel)')->rows(5)->maxLength(5000)
                    ->helperText('Bijvoorbeeld betaling in termijnen. Je algemene voorwaarden worden automatisch gelinkt als die pagina live staat.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('Voorstel')->weight('medium')->searchable()
                    ->description(fn (Proposal $r) => $r->client_name.($r->client_company ? " · {$r->client_company}" : '')),
                TextColumn::make('state')->label('Status')->badge()
                    ->state(fn (Proposal $r) => $r->state())
                    ->formatStateUsing(fn ($state) => Proposal::STATES[$state] ?? $state)
                    ->color(fn ($state) => Proposal::STATE_COLORS[$state] ?? 'gray'),
                TextColumn::make('total')->label('Eenmalig')->state(fn (Proposal $r) => isset($r->totals()['once']) ? PriceCalculator::money($r->totals()['once']) : null)->placeholder('-'),
                TextColumn::make('view_count')->label('Bekeken')->formatStateUsing(fn ($state) => $state.'x')
                    ->description(fn (Proposal $r) => $r->last_viewed_at?->timezone(config('site.timezone'))->format('d-m H:i')),
                TextColumn::make('updated_at')->label('Gewijzigd')->since()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([EditAction::make()->label(fn (Proposal $r) => $r->isDecided() ? 'Bekijken' : 'Bewerken')]);
    }

    public static function getPages(): array
    {
        return ['index' => ListProposals::route('/'), 'create' => CreateProposal::route('/create'), 'edit' => EditProposal::route('/{record}/edit')];
    }
}
