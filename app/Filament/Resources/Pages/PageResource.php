<?php

namespace App\Filament\Resources\Pages;

use App\Content\Blocks\Blocks;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Support\PublishingUi;
use App\Filament\Support\Translated;
use App\Models\Page;
use BackedEnum;
use Closure;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Inhoud';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'pagina';

    protected static ?string $pluralModelLabel = 'pagina’s';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            ...PublishingUi::callouts(),
            Section::make('Pagina')->schema([
                TextInput::make('key')->label('Pagina (systeemnaam)')->required()->alphaDash()->maxLength(40)->unique(ignoreRecord: true)
                    ->disabled(fn (?Model $record) => $record?->exists ?? false)->dehydrated(fn (?Model $record) => ! ($record?->exists ?? false)),
                Hidden::make('kind')->default('legal'),
                Translated::text('title', 'Titel', true),
            ]),
            Section::make('Onderdelen')->description('Zet onderdelen in de gewenste volgorde en verberg wat je niet wilt tonen. De opmaak ligt vast.')->schema([
                Builder::make('blocks')->label('Onderdelen van de pagina')
                    ->blocks(fn (?Model $record) => collect(Blocks::allowedFor($record?->key ?? 'legal'))->map(fn ($b) => $b->builderBlock())->values()->all())
                    ->reorderableWithButtons()->collapsible()->collapsed()->blockNumbers(false)->addActionLabel('Onderdeel toevoegen')
                    ->rule(fn (?Model $record) => self::uniqueSections($record?->key ?? 'legal')),
            ]),
            Section::make('Adres')->collapsed()->columns(['default' => 1, 'md' => 2])->visible(fn (?Model $record) => $record?->kind === 'legal')->schema([
                TextInput::make('slug_nl')->label('Webadres (NL)')->alphaDash(),
                TextInput::make('slug_en')->label('Webadres (EN)')->alphaDash(),
            ]),
            PublishingUi::seoSection(),
        ]);
    }

    /** Sections marked as single-use may appear only once. */
    private static function uniqueSections(string $pageKey): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($pageKey) {
            $counts = collect($value ?? [])->pluck('type')->countBy();

            foreach ($counts as $type => $count) {
                $block = Blocks::find($pageKey, $type);

                if ($block && $block->singleton() && $count > 1) {
                    $fail("Het onderdeel \"{$block->label()}\" mag maar één keer op deze pagina staan.");
                }
            }
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title.nl')->label('Pagina')->weight('medium'),
                TextColumn::make('key')->label('Systeemnaam')->color('gray'),
                ...PublishingUi::tableColumns(),
            ])
            ->defaultSort('id')
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
