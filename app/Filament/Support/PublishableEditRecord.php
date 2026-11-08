<?php

namespace App\Filament\Support;

use App\Support\CannotPublish;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Edit page for every publishable content type.
 *
 * Saving never changes live content: on a published record the changes become
 * an unpublished draft. Publishing is an explicit action and is refused while
 * required translations or alt texts are missing.
 */
abstract class PublishableEditRecord extends EditRecord
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->getRecord()->withDraft()->attributesToArray();
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->saveFromForm($data);

        return $record;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return $this->getRecord()->isPublished()
            ? 'Concept opgeslagen. De live versie is ongewijzigd.'
            : 'Opgeslagen';
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('previewNl')->label('Voorbeeld in het Nederlands')->icon('heroicon-o-eye')
                    ->url(fn () => $this->getRecord()->previewUrl('nl'), shouldOpenInNewTab: true),
                Action::make('previewEn')->label('Voorbeeld in het Engels')->icon('heroicon-o-eye')
                    ->url(fn () => $this->getRecord()->previewUrl('en'), shouldOpenInNewTab: true),
            ])->label('Voorbeeld')->icon('heroicon-o-eye')->button()->color('gray'),

            Action::make('publish')
                ->label(fn () => $this->getRecord()->isPublished() ? 'Wijzigingen publiceren' : 'Publiceren')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->visible(fn () => ! $this->getRecord()->isPublished() || $this->getRecord()->hasDraftChanges())
                ->authorize(fn () => auth()->user()->can('publish', $this->getRecord()))
                ->requiresConfirmation()
                ->modalHeading('Publiceren?')
                ->modalDescription('De huidige gepubliceerde versie wordt vervangen. Dit is meteen zichtbaar op de website.')
                ->action(function () {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);

                    try {
                        $this->getRecord()->refresh()->publish();
                    } catch (CannotPublish $e) {
                        Notification::make()->danger()->title('Publiceren geblokkeerd')
                            ->body(implode("\n", $e->issues))->persistent()->send();

                        return;
                    }

                    $this->fillForm();
                    Notification::make()->success()->title('Gepubliceerd')->send();
                }),

            Action::make('discard')
                ->label('Wijzigingen verwerpen')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->visible(fn () => $this->getRecord()->isPublished() && $this->getRecord()->hasDraftChanges())
                ->requiresConfirmation()
                ->modalDescription('Je concept verdwijnt. De live versie blijft zoals ze is.')
                ->action(function () {
                    $this->getRecord()->discardDraft();
                    $this->fillForm();
                    Notification::make()->success()->title('Wijzigingen verworpen')->send();
                }),

            Action::make('unpublish')
                ->label('Offline halen')
                ->icon('heroicon-o-eye-slash')
                ->color('gray')
                ->visible(fn () => $this->getRecord()->isPublished())
                ->authorize(fn () => auth()->user()->can('publish', $this->getRecord()))
                ->requiresConfirmation()
                ->modalDescription('De pagina is daarna niet meer zichtbaar op de website.')
                ->action(function () {
                    $this->getRecord()->unpublish();
                    $this->fillForm();
                    Notification::make()->success()->title('Offline gehaald')->send();
                }),

            DeleteAction::make(),
        ];
    }
}
