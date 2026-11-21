<?php

namespace App\Filament\Resources\Leads;

use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Leads\PriceCalculator;
use App\Mail\AppointmentFormat;
use App\Models\Lead;
use App\Models\PriceItem;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Every lead from the calculator, the request wizard, appointments and the website check. */
class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Inbox';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'lead';

    protected static ?string $pluralModelLabel = 'leads';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Lead::query()->reachable()->where('status', 'new')->count();

        return $count ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        $names = fn () => PriceItem::query()->get()->mapWithKeys(fn ($i) => [$i->key => loc($i->name, 'nl') ?? $i->key]);
        $deadlines = trans('leads.request.deadlines', [], 'nl');

        return $schema->columns(1)->components([
            Section::make('Contact')->columns(['default' => 1, 'md' => 3])->schema([
                TextEntry::make('name')->label('Naam')->placeholder('—'),
                TextEntry::make('email')->label('E-mail')->placeholder('—')->copyable()->url(fn (Lead $r) => $r->email ? 'mailto:'.$r->email : null),
                TextEntry::make('phone')->label('Telefoon')->placeholder('—')->url(fn (Lead $r) => $r->phone ? 'tel:'.preg_replace('/[^0-9+]/', '', $r->phone) : null),
                TextEntry::make('company')->label('Bedrijf')->placeholder('—'),
                TextEntry::make('source')->label('Bron')->formatStateUsing(fn ($state) => Lead::SOURCES[$state] ?? $state)->badge(),
                TextEntry::make('status')->label('Status')->formatStateUsing(fn ($state) => Lead::STATUSES[$state] ?? $state)->badge(),
                TextEntry::make('contact_consent')->label('Toestemming contact')->formatStateUsing(fn ($state) => $state ? 'Ja' : 'Nee'),
                TextEntry::make('submitted_at')->label('Aanvraag verstuurd')->dateTime('d-m-Y H:i')->timezone('Europe/Amsterdam')->placeholder('Niet (concept of andere bron)'),
                TextEntry::make('locale')->label('Taal')->formatStateUsing(fn ($state) => strtoupper($state)),
            ]),
            Section::make('Calculator')->columns(['default' => 1, 'md' => 2])->visible(fn (Lead $r) => $r->choice('calculator') !== null)->schema([
                TextEntry::make('estimate')->label('Indicatie')->state(fn (Lead $r) => PriceCalculator::range($r->estimate_min, $r->estimate_max)),
                TextEntry::make('calculator_choices')->label('Keuzes')->state(fn (Lead $r) => collect([$r->choice('calculator.project'), $r->choice('calculator.size'), ...$r->choice('calculator.extras', []), $r->choice('calculator.deadline')])
                    ->filter()->map(fn ($k) => $names()[$k] ?? $k)->implode(' · ')),
            ]),
            Section::make('Aanvraag')->columns(['default' => 1, 'md' => 2])->visible(fn (Lead $r) => $r->choice('wizard') !== null)->schema([
                TextEntry::make('w_project')->label('Soort project')->state(fn (Lead $r) => ($k = $r->choice('wizard.project')) ? ($k === 'other' ? 'Iets anders' : ($names()[$k] ?? $k)) : null)->placeholder('—'),
                TextEntry::make('w_budget')->label('Budget')->state(fn (Lead $r) => $r->choice('wizard.budget'))->placeholder('—'),
                TextEntry::make('w_deadline')->label('Deadline')->state(fn (Lead $r) => $deadlines[$r->choice('wizard.deadline')] ?? null)->placeholder('—'),
                TextEntry::make('w_examples')->label('Voorbeeldsites')->state(fn (Lead $r) => implode("\n", $r->choice('wizard.examples', [])) ?: null)->placeholder('—'),
                ...array_map(fn ($key, $label) => TextEntry::make('w_'.$key)->label($label)->columnSpanFull()->placeholder('—')
                    ->state(fn (Lead $r) => $r->choice('wizard.'.$key) ?: null)->html()->formatStateUsing(fn (?string $state) => nl2br(e($state ?? ''))),
                    ['summary', 'goal', 'audience', 'features'], ['Wat', 'Doel', 'Doelgroep', 'Functies']),
            ]),
            Section::make('Afspraken')->visible(fn (Lead $r) => $r->appointments()->exists())->schema([
                TextEntry::make('appointments_list')->label('')->html()->state(fn (Lead $r) => $r->appointments()->with('type')->latest('starts_at')->get()
                    ->map(fn ($a) => e(AppointmentFormat::when($a, 'nl').' · '.(loc($a->type->name, 'nl') ?? '').' · '.($a->status === 'confirmed' ? 'bevestigd' : 'geannuleerd')))->implode('<br>')),
            ]),
            Section::make('Website-check')->visible(fn (Lead $r) => $r->choice('scan') !== null)->schema([
                TextEntry::make('scan_url')->label('Gescande pagina')->state(fn (Lead $r) => $r->choice('scan.url'))
                    ->url(fn (Lead $r) => $r->choice('scan.report'), shouldOpenInNewTab: true),
            ]),
            Section::make('Notities')->schema([
                TextEntry::make('notes')->label('')->placeholder('Nog geen notities.')->html()->formatStateUsing(fn (?string $state) => nl2br(e($state ?? ''))),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Naam')->searchable(['name', 'email', 'company'])->placeholder('Anoniem concept')->description(fn (Lead $r) => $r->email),
                TextColumn::make('source')->label('Bron')->badge()->formatStateUsing(fn ($state) => Lead::SOURCES[$state] ?? $state),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn ($state) => Lead::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'new' => 'warning', 'contacted' => 'info', 'quote' => 'primary', 'won' => 'success', default => 'gray'
                    }),
                TextColumn::make('estimate')->label('Indicatie')->state(fn (Lead $r) => PriceCalculator::range($r->estimate_min, $r->estimate_max))->placeholder('—')->toggleable(),
                TextColumn::make('updated_at')->label('Laatste activiteit')->dateTime('d-m-Y H:i')->timezone('Europe/Amsterdam')->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                TernaryFilter::make('reachable')->label('Met contactgegevens')->default(true)
                    ->queries(true: fn (Builder $q) => $q->whereNotNull('email'), false: fn (Builder $q) => $q->whereNull('email'), blank: fn (Builder $q) => $q),
                SelectFilter::make('status')->label('Status')->options(Lead::STATUSES),
                SelectFilter::make('source')->label('Bron')->options(Lead::SOURCES),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListLeads::route('/'), 'view' => ViewLead::route('/{record}')];
    }
}
