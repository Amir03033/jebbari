<?php

namespace App\Filament\Resources\Previews;

use App\Enums\PreviewStatus;
use App\Filament\Resources\Previews\Pages\CreatePreview;
use App\Filament\Resources\Previews\Pages\EditPreview;
use App\Filament\Resources\Previews\Pages\ListPreviews;
use App\Filament\Resources\Previews\Pages\ViewPreview;
use App\Models\Preview;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Client website previews on the preview subdomain. See docs/PREVIEW-DEPLOY.md. */
class PreviewResource extends Resource
{
    protected static ?string $model = Preview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    protected static string|\UnitEnum|null $navigationGroup = 'Systeem';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'preview';

    protected static ?string $pluralModelLabel = 'previews';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Klantwebsite')->columns(['default' => 1, 'md' => 2])->schema([
                TextInput::make('client_name')->label('Klantnaam')->required()->maxLength(120),
                TextInput::make('repo_url')->label('GitHub-repository')->required()->maxLength(200)
                    ->placeholder('https://github.com/eigenaar/repository')
                    ->helperText('Alleen https://github.com/... Private repositories werken met PREVIEW_GITHUB_TOKEN.')
                    ->rule(fn () => function (string $attribute, $value, Closure $fail) {
                        if (Preview::normalizeRepoUrl($value) === null) {
                            $fail('Gebruik een GitHub-URL in de vorm https://github.com/eigenaar/repository.');
                        }
                    })
                    ->dehydrateStateUsing(fn (?string $state) => Preview::normalizeRepoUrl($state) ?? $state),
                TextInput::make('branch')->label('Branch')->required()->default('main')->maxLength(100)
                    ->rule(fn () => function (string $attribute, $value, Closure $fail) {
                        if (! Preview::validBranch($value)) {
                            $fail('Ongeldige branchnaam.');
                        }
                    }),
                Select::make('locale')->label('Taal van de klantpagina')->options(['nl' => 'Nederlands', 'en' => 'English'])->default('nl')->required()->selectablePlaceholder(false),
                DateTimePicker::make('expires_at')->label('Link geldig tot')->required()->seconds(false)->timezone('Europe/Amsterdam')
                    ->default(fn () => now()->addDays(config('preview.lifetime_days'))),
                Toggle::make('dev_dependencies')->label('Composer dev-pakketten installeren')
                    ->helperText('Nodig als de seeders factories of Faker gebruiken.'),
                Toggle::make('keep_database')->label('Database behouden bij een nieuwe deploy')->default(true)
                    ->helperText('Inhoud, accounts en uploads blijven staan; alleen nieuwe migraties draaien. De eerste deploy begint altijd met een lege database.'),
                Toggle::make('auto_update')->label('Automatisch bijwerken')->default(true)
                    ->helperText('Binnen een minuut na een nieuwe commit op deze branch wordt de preview opnieuw gedeployd.'),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Preview')->columns(['default' => 1, 'md' => 2])->poll(fn (Preview $record) => $record->status->isBusy() ? '3s' : null)->schema([
                TextEntry::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (PreviewStatus $state) => $state->label())
                    ->color(fn (PreviewStatus $state) => $state->color()),
                TextEntry::make('review_url')->label('Link voor de klant')->copyable()
                    ->state(fn (Preview $record) => $record->reviewUrl()),
                TextEntry::make('repo_url')->label('Repository')->url(fn (Preview $record) => $record->repo_url, true),
                TextEntry::make('branch')->label('Branch'),
                TextEntry::make('deployed_at')->label('Laatst gedeployd')->dateTime('d-m-Y H:i')->timezone('Europe/Amsterdam')->placeholder('—'),
                TextEntry::make('expires_at')->label('Geldig tot')->dateTime('d-m-Y H:i')->timezone('Europe/Amsterdam')
                    ->color(fn (Preview $record) => $record->isExpired() ? 'danger' : null),
                TextEntry::make('commit_sha')->label('Commit')->placeholder('—')
                    ->formatStateUsing(fn (?string $state) => $state ? substr($state, 0, 7) : null)
                    ->url(fn (Preview $record) => $record->commit_sha && $record->repoPath() ? 'https://github.com/'.$record->repoPath().'/commit/'.$record->commit_sha : null, true),
                TextEntry::make('options')->label('Bij een nieuwe deploy')
                    ->state(fn (Preview $record) => ($record->keepsDatabase() ? 'database blijft staan' : 'database wordt leeggemaakt')
                        .($record->auto_update ? ' · automatisch bij een nieuwe commit' : ' · alleen handmatig')),
            ]),
            Section::make('Reactie van de klant')->columns(['default' => 1, 'md' => 2])->schema([
                TextEntry::make('approved_at')->label('Goedgekeurd')->dateTime('d-m-Y H:i')->timezone('Europe/Amsterdam')->placeholder('Nog niet'),
                TextEntry::make('change_requested_at')->label('Wijzigingen gevraagd')->dateTime('d-m-Y H:i')->timezone('Europe/Amsterdam')->placeholder('—'),
                TextEntry::make('change_request')->label('Gevraagde wijzigingen')->columnSpanFull()->placeholder('—')->html()
                    ->formatStateUsing(fn (?string $state) => nl2br(e($state ?? ''))),
            ]),
            Section::make('Log')->collapsible()->poll(fn (Preview $record) => $record->status->isBusy() ? '3s' : null)->schema([
                TextEntry::make('log')->hiddenLabel()->placeholder('Nog geen uitvoer.')->html()
                    ->formatStateUsing(fn (?string $state) => '<pre style="white-space:pre-wrap;font-size:12px;line-height:1.5;max-height:32rem;overflow:auto">'.e($state ?? '').'</pre>'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll(fn () => Preview::busy() ? '3s' : null)
            ->columns([
                TextColumn::make('client_name')->label('Klant')->searchable()
                    ->description(fn (Preview $r) => str_replace(['https://github.com/', '.git'], '', $r->repo_url).' · '.$r->branch),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (PreviewStatus $state) => $state->label())
                    ->color(fn (PreviewStatus $state) => $state->color()),
                TextColumn::make('approved_at')->label('Klant')
                    ->state(fn (Preview $r) => $r->approved_at ? 'Goedgekeurd' : ($r->changesSentForThisBuild() ? 'Wijzigingen' : null))
                    ->badge()->color(fn (Preview $r) => $r->approved_at ? 'success' : 'warning')->placeholder('—'),
                TextColumn::make('deployed_at')->label('Gedeployd')->dateTime('d-m-Y H:i')->timezone('Europe/Amsterdam')->placeholder('—')->sortable(),
                TextColumn::make('expires_at')->label('Geldig tot')->dateTime('d-m-Y')->timezone('Europe/Amsterdam')
                    ->color(fn (Preview $r) => $r->isExpired() ? 'danger' : null),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make(), static::deployAction()]);
    }

    /** "Deploy": puts this preview on the subdomain, replacing whatever is there. */
    public static function deployAction(): Action
    {
        return Action::make('deploy')->label('Deploy')->icon('heroicon-o-rocket-launch')->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Deze preview deployen?')
            ->modalDescription('De huidige preview op de subdomain wordt vervangen en de database wordt opnieuw opgebouwd. Andere klantlinks stoppen met werken.')
            ->disabled(fn () => Preview::busy())
            ->action(function (Preview $record) {
                if (Preview::busy()) {
                    Notification::make()->warning()->title('Er loopt al een build. Wacht tot die klaar is.')->send();

                    return;
                }

                $record->deploy();
                Notification::make()->success()->title('In de wachtrij gezet')->body('De queue worker start de build binnen een minuut.')->send();
            });
    }

    /** "Preview wissen": empties the subdomain and its database. */
    public static function clearAction(): Action
    {
        return Action::make('clear')->label('Preview wissen')->icon('heroicon-o-trash')->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Preview wissen?')
            ->modalDescription('De subdomain wordt leeggemaakt en alle tabellen in de preview-database worden verwijderd. Klantlinks stoppen met werken.')
            ->action(function () {
                Preview::clearServer();
                Notification::make()->success()->title('Wordt gewist')->body('De queue worker ruimt de preview binnen een minuut op.')->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPreviews::route('/'),
            'create' => CreatePreview::route('/create'),
            'view' => ViewPreview::route('/{record}'),
            'edit' => EditPreview::route('/{record}/edit'),
        ];
    }
}
