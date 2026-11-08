<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Support\PublishingUi;
use App\Filament\Support\Translated;
use App\Models\Post;
use App\Models\PostCategory;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|\UnitEnum|null $navigationGroup = 'Inhoud';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'artikel';

    protected static ?string $pluralModelLabel = 'artikelen';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            ...PublishingUi::callouts(),
            Section::make('Artikel')->schema([
                Translated::text('title', 'Titel', true),
                Translated::textarea('excerpt', 'Samenvatting (voor overzicht en delen)', true, 3),
                Translated::rich('body', 'Artikeltekst', true),
            ]),
            Section::make('Indeling')->columns(['default' => 1, 'md' => 2])->schema([
                Select::make('category_id')->label('Categorie')->placeholder('Geen categorie')
                    ->options(fn () => PostCategory::query()->orderBy('sort')->get()->mapWithKeys(fn ($c) => [$c->id => $c->t('name', 'nl') ?: "Categorie {$c->id}"])->all()),
                PublishingUi::mediaSelect('cover_media_id', 'Omslagafbeelding'),
            ]),
            PublishingUi::pageSection(false),
            PublishingUi::seoSection(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title.nl')->label('Titel')->weight('medium')
                    ->searchable(query: fn ($query, $search) => $query->where('title', 'like', "%{$search}%")),
                TextColumn::make('category.name.nl')->label('Categorie')->placeholder('—'),
                ...PublishingUi::tableColumns(),
                TextColumn::make('published_at')->label('Publicatiedatum')->dateTime('d-m-Y H:i')->timezone('Europe/Amsterdam')->sortable()->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([TrashedFilter::make()->label('Verwijderd')])
            ->recordActions([EditAction::make(), RestoreAction::make()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
