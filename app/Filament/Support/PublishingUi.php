<?php

namespace App\Filament\Support;

use App\Models\MediaAsset;
use App\Support\MediaSizes;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** Shared pieces of the content forms and tables. */
class PublishingUi
{
    private const STATE_LABELS = [
        'draft' => 'Concept',
        'live' => 'Gepubliceerd',
        'live_changes' => 'Gepubliceerd + wijzigingen',
        'scheduled' => 'Gepland',
    ];

    private const STATE_COLORS = ['draft' => 'gray', 'live' => 'success', 'live_changes' => 'warning', 'scheduled' => 'info'];

    /** Banners at the top of a content form: workflow state and missing translations. */
    public static function callouts(): array
    {
        return [
            Callout::make(fn (?Model $record) => match ($record?->workflowState()) {
                'live_changes' => 'Er zijn niet-gepubliceerde wijzigingen',
                'scheduled' => 'Gepland voor publicatie',
                default => 'Concept: nog niet gepubliceerd',
            })
                ->description(fn (?Model $record) => match ($record?->workflowState()) {
                    'live_changes' => 'De versie op de website is ongewijzigd. Bekijk het voorbeeld en kies Wijzigingen publiceren als je tevreden bent.',
                    'scheduled' => 'Deze inhoud verschijnt automatisch op '.$record?->published_at?->timezone('Europe/Amsterdam')->format('d-m-Y H:i').'.',
                    default => 'Deze inhoud is niet zichtbaar op de website tot je op Publiceren drukt.',
                })
                ->info()
                ->visible(fn (?Model $record) => $record?->exists && $record->workflowState() !== 'live')
                ->columnSpanFull(),

            Callout::make('Vertalingen onvolledig: publiceren is geblokkeerd')
                ->description(function (?Model $record) {
                    $lines = [];
                    foreach ($record?->translationGaps() ?? [] as $locale => $labels) {
                        if ($labels) {
                            $lines[] = strtoupper($locale).': '.implode(', ', array_unique($labels));
                        }
                    }

                    return implode(' · ', $lines);
                })
                ->warning()
                ->visible(fn (?Model $record) => $record?->exists && ! $record->isFullyTranslated())
                ->columnSpanFull(),
        ];
    }

    /** Slugs, ordering and publication date. */
    public static function pageSection(bool $withSort = true, string $dateLabel = 'Publicatiedatum (leeg = direct bij publiceren)'): Section
    {
        return Section::make('Adres en volgorde')->collapsed()->columns(['default' => 1, 'md' => 2])->schema([
            TextInput::make('slug_nl')->label('Webadres (NL)')->helperText('Leeg laten om het automatisch uit de titel te maken.')->maxLength(120)->alphaDash(),
            TextInput::make('slug_en')->label('Webadres (EN)')->helperText('Leeg laten om het automatisch uit de titel te maken.')->maxLength(120)->alphaDash(),
            ...($withSort ? [TextInput::make('sort')->label('Volgorde')->numeric()->default(0)->helperText('Laagste nummer staat bovenaan.')] : []),
            DateTimePicker::make('published_at')->label($dateLabel)->seconds(false)
                ->disabled(fn (?Model $record) => $record?->isPublished() ?? false)->dehydrated(fn (?Model $record) => ! ($record?->isPublished() ?? false)),
        ]);
    }

    public static function seoSection(): Section
    {
        return Section::make('Zoekmachines en delen')->collapsed()->schema([
            Translated::text('seo.title', 'SEO-titel (max. 60 tekens)', false, 70),
            Translated::textarea('seo.description', 'SEO-beschrijving (max. 160 tekens)', false, 3),
            self::mediaSelect('seo.og_media_id', 'Afbeelding bij delen (optioneel)'),
            Toggle::make('seo.noindex')->label('Niet laten indexeren door zoekmachines'),
        ]);
    }

    /** A select over the media library, with a quick upload inside the picker. */
    public static function mediaSelect(string $name, string $label): Select
    {
        return Select::make($name)->label($label)
            ->options(fn () => MediaAsset::query()->latest()->limit(300)->pluck('title', 'id')->all())
            ->searchable()->placeholder('Kies uit de mediabibliotheek')
            ->createOptionForm([
                TextInput::make('title')->label('Naam (alleen intern)')->required()->maxLength(120),
                FileUpload::make('upload')->label('Afbeelding')->image()->disk('local')->directory('tmp-uploads')->visibility('private')
                    ->acceptedFileTypes(MediaSizes::mimes())->maxSize(MediaSizes::MAX_KB)->required(),
                Translated::text('alt', 'Alt-tekst (beschrijf wat je ziet)', false, 200),
            ])
            ->createOptionUsing(function (array $data): int {
                $asset = MediaAsset::create(['title' => $data['title'], 'alt' => $data['alt'] ?? null]);
                $asset->addMedia(Storage::disk('local')->path($data['upload']))->toMediaCollection('file');

                return $asset->getKey();
            });
    }

    /** Status and translation columns for content tables. */
    public static function tableColumns(): array
    {
        return [
            TextColumn::make('workflow')->label('Status')->badge()
                ->state(fn (Model $record) => self::STATE_LABELS[$record->workflowState()])
                ->color(fn (Model $record) => self::STATE_COLORS[$record->workflowState()]),
            TextColumn::make('translations')->label('Talen')->badge()
                ->state(function (Model $record) {
                    $gaps = $record->translationGaps();

                    return 'NL '.($gaps['nl'] ? '✗' : '✓').'  EN '.($gaps['en'] ? '✗' : '✓');
                })
                ->color(fn (Model $record) => $record->isFullyTranslated() ? 'success' : 'warning'),
        ];
    }
}
