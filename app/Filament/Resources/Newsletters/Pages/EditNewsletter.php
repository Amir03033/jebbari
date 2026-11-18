<?php

namespace App\Filament\Resources\Newsletters\Pages;

use App\Filament\Resources\Newsletters\NewsletterResource;
use App\Jobs\SendNewsletter;
use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\Subscriber;
use App\Support\Settings;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EditNewsletter extends EditRecord
{
    protected static string $resource = NewsletterResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Newsletter $newsletter */
        $newsletter = $this->getRecord();

        return [
            Action::make('test')->label('Testmail naar mij')->icon(Heroicon::OutlinedBeaker)->color('gray')
                ->hidden(fn () => $newsletter->isSent())
                ->action(function () use ($newsletter) {
                    $to = auth()->user()->email ?: Settings::get('contact.email');

                    try {
                        Mail::to($to)->send(new NewsletterMail($newsletter->fresh(), null, 'nl'));
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->danger()->title('Testmail mislukt')->body('Controleer de mailinstellingen van de server.')->send();

                        return;
                    }

                    Notification::make()->success()->title("Testmail verstuurd naar $to")->body('Sla eerst op als je net iets hebt gewijzigd.')->send();
                }),

            Action::make('send')->label('Versturen')->icon(Heroicon::OutlinedPaperAirplane)
                ->hidden(fn () => $newsletter->isSent())
                ->requiresConfirmation()
                ->modalHeading('Nieuwsbrief versturen?')
                ->modalDescription(fn () => 'Hij gaat naar '.Subscriber::query()->active()->count().' bevestigde abonnee(s). Dit kan niet ongedaan worden gemaakt. Sla eerst je wijzigingen op.')
                ->modalSubmitActionLabel('Ja, versturen')
                ->action(function () use ($newsletter) {
                    $fresh = $newsletter->fresh();
                    $nl = $fresh->in('nl');

                    if (blank($nl['subject']) || blank(strip_tags($nl['body']))) {
                        Notification::make()->warning()->title('Vul eerst onderwerp en tekst (NL) in en sla op.')->send();

                        return;
                    }

                    $count = SendNewsletter::toEveryone($fresh);
                    activity()->causedBy(auth()->user())->performedOn($fresh)->event('sent')->withProperties(['recipients' => $count])->log('newsletter_sent');
                    Notification::make()->success()->title('Wordt verstuurd')->body("Naar $count abonnee(s). De mails gaan via de wachtrij de deur uit.")->send();
                    $this->redirect(NewsletterResource::getUrl('edit', ['record' => $fresh]));
                }),

            DeleteAction::make()->hidden(fn () => $newsletter->isSent()),
        ];
    }

    protected function getFormActions(): array
    {
        return $this->getRecord()->isSent() ? [] : parent::getFormActions();
    }

    protected function beforeSave(): void
    {
        abort_if($this->getRecord()->isSent(), 403);
    }
}
