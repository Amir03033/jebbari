<?php

namespace App\Filament\Resources\Newsletters;

use App\Filament\Resources\Newsletters\Pages\CreateNewsletter;
use App\Filament\Resources\Newsletters\Pages\EditNewsletter;
use App\Filament\Resources\Newsletters\Pages\ListNewsletters;
use App\Filament\Support\Translated;
use App\Models\Newsletter;
use App\Models\Post;
use App\Models\Subscriber;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Write a newsletter and send it to every confirmed subscriber (docs/NEWSLETTER.md). Owner only. */
class NewsletterResource extends Resource
{
    protected static ?string $model = Newsletter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperAirplane;

    protected static string|\UnitEnum|null $navigationGroup = 'Inbox';

    protected static ?int $navigationSort = 21;

    protected static ?string $modelLabel = 'nieuwsbrief';

    protected static ?string $pluralModelLabel = 'nieuwsbrieven';

    public static function canAccess(): bool
    {
        return auth()->user()?->isOwner() ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        $sent = fn (?Newsletter $record) => $record?->isSent() ?? false;

        return $schema->columns(1)->components([
            Callout::make(fn (?Newsletter $record) => 'Verstuurd op '.$record?->sent_at?->timezone(config('site.timezone'))->format('d-m-Y H:i'))
                ->description(fn (?Newsletter $record) => "Naar {$record?->recipients} abonnee(s). Een verstuurde nieuwsbrief kan niet meer worden gewijzigd.")
                ->success()->visible($sent)->columnSpanFull(),

            Callout::make(fn () => Subscriber::query()->active()->count().' bevestigde abonnee(s)')
                ->description('Iedereen krijgt de nieuwsbrief in de taal waarin die zich aanmeldde. Ontbreekt de Engelse tekst, dan krijgen Engelstalige abonnees de Nederlandse.')
                ->info()->hidden($sent)->columnSpanFull(),

            Section::make('Nieuwsbrief')->disabled($sent)->schema([
                Select::make('post_id')->label('Over een artikel (optioneel)')
                    ->options(fn () => Post::query()->published()->latest('published_at')->limit(50)->get()->mapWithKeys(fn (Post $p) => [$p->id => $p->title['nl'] ?? '#'.$p->id]))
                    ->searchable()->live()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($post = Post::find($state)) {
                            $set('subject', ['nl' => $post->title['nl'] ?? '', 'en' => $post->title['en'] ?? '']);
                            $set('body', [
                                'nl' => '<p>'.e($post->excerpt['nl'] ?? '').'</p>',
                                'en' => '<p>'.e($post->excerpt['en'] ?? '').'</p>',
                            ]);
                        }
                    })
                    ->helperText('Vult onderwerp en tekst met de titel en samenvatting, en zet een knop "Lees het artikel" onder de mail.'),
                Translated::text('subject', 'Onderwerp', true, 140),
                Translated::rich('body', 'Tekst', true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject.nl')->label('Onderwerp')->weight('medium')->searchable(),
                TextColumn::make('state')->label('Status')->badge()
                    ->state(fn (Newsletter $n) => $n->isSent() ? 'Verstuurd' : 'Concept')
                    ->color(fn (Newsletter $n) => $n->isSent() ? 'success' : 'gray'),
                TextColumn::make('recipients')->label('Ontvangers')->formatStateUsing(fn ($state, Newsletter $n) => $n->isSent() ? $state : '-'),
                TextColumn::make('sent_at')->label('Verstuurd op')->dateTime('d-m-Y H:i', config('site.timezone'))->placeholder('-')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make()->label(fn (Newsletter $n) => $n->isSent() ? 'Bekijken' : 'Bewerken')]);
    }

    public static function getPages(): array
    {
        return ['index' => ListNewsletters::route('/'), 'create' => CreateNewsletter::route('/create'), 'edit' => EditNewsletter::route('/{record}/edit')];
    }
}
