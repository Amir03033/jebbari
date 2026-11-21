<?php

namespace App\Filament\Pages\Settings;

use App\Support\Settings;
use App\Support\SiteOffline;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;

/**
 * One switch to take the public website offline and back. While offline,
 * visitors see a calm offline page (503); the admin keeps working and
 * signed-in team members still see the site, with a notice.
 */
class SiteStatus extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPower;

    protected static ?string $navigationLabel = 'Website online/offline';

    protected static ?string $title = 'Website online of offline';

    protected static ?int $navigationSort = 1;

    protected static function group(): string
    {
        return 'offline';
    }

    public static function getNavigationBadge(): ?string
    {
        return SiteOffline::isOn() ? 'Offline' : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    protected function components(): array
    {
        return [
            Callout::make(fn () => SiteOffline::isOn() ? 'De website is offline voor bezoekers' : 'De website is online')
                ->description(fn () => SiteOffline::isOn()
                    ? 'Bezoekers zien de offline-pagina hieronder. Jij en je team zien de site gewoon (met een melding) zolang jullie zijn ingelogd. De beheeromgeving werkt normaal.'
                    : 'Iedereen kan de website bezoeken. Met de knop rechtsboven zet je hem in één keer offline.')
                ->color(fn () => SiteOffline::isOn() ? 'danger' : 'success')
                ->columnSpanFull(),

            Section::make('Wat bezoekers zien als de site offline is')
                ->description('Laat leeg voor de standaardtekst: "De website is tijdelijk offline. Kom later nog eens terug." Je e-mailadres uit Contact staat er altijd onder.')
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    Textarea::make('message_nl')->label('Bericht (NL)')->rows(3)->maxLength(300),
                    Textarea::make('message_en')->label('Bericht (EN)')->rows(3)->maxLength(300),
                    DateTimePicker::make('back_at')->label('Verwacht terug (optioneel)')->seconds(false)
                        ->timezone(config('site.timezone'))
                        ->helperText('Wordt getoond als "Verwacht terug: ..." zolang dit moment in de toekomst ligt.'),
                ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('goOffline')
                ->label('Zet website offline')
                ->icon(Heroicon::OutlinedPower)
                ->color('danger')
                ->visible(fn () => ! SiteOffline::isOn())
                ->requiresConfirmation()
                ->modalHeading('Website offline zetten?')
                ->modalDescription('Bezoekers zien direct de offline-pagina. De beheeromgeving blijft werken en je kunt de site hier op elk moment weer online zetten.')
                ->modalSubmitActionLabel('Ja, zet offline')
                ->action(fn () => $this->switch(true)),

            Action::make('goOnline')
                ->label('Zet website weer online')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn () => SiteOffline::isOn())
                ->action(fn () => $this->switch(false)),

            Action::make('view')
                ->label('Bekijk offline-pagina')
                ->color('gray')
                ->url(fn () => route('offline.preview'), shouldOpenInNewTab: true),
        ];
    }

    /** Keeps the switch when the message form is saved (the form does not hold it). */
    public function save(): void
    {
        Settings::save('offline', ['enabled' => SiteOffline::isOn()] + $this->form->getState());

        activity()->causedBy(auth()->user())->event('updated')->withProperties(['settings' => 'offline'])->log('settings_updated');

        Notification::make()->success()->title('Opgeslagen')->send();
    }

    private function switch(bool $offline): void
    {
        Settings::save('offline', ['enabled' => $offline] + $this->form->getState());

        activity()->causedBy(auth()->user())->event('updated')
            ->withProperties(['settings' => 'offline', 'enabled' => $offline])
            ->log($offline ? 'site_offline' : 'site_online');

        $offline
            ? Notification::make()->danger()->title('De website is offline')->body('Bezoekers zien nu de offline-pagina.')->send()
            : Notification::make()->success()->title('De website is weer online')->send();
    }
}
