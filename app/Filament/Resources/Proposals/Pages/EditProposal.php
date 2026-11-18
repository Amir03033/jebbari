<?php

namespace App\Filament\Resources\Proposals\Pages;

use App\Filament\Resources\Proposals\ProposalResource;
use App\Mail\ProposalSent;
use App\Models\Proposal;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EditProposal extends EditRecord
{
    protected static string $resource = ProposalResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Proposal $proposal */
        $proposal = $this->getRecord();

        return [
            Action::make('open')->label('Bekijk als klant')->icon(Heroicon::OutlinedEye)->color('gray')
                ->url(fn () => $proposal->url(), shouldOpenInNewTab: true),

            Action::make('send')->label(fn () => $proposal->sent_at ? 'Opnieuw versturen' : 'Versturen naar klant')
                ->icon(Heroicon::OutlinedPaperAirplane)
                ->visible(fn () => $proposal->isOpen())
                ->requiresConfirmation()
                ->modalHeading('Voorstel versturen?')
                ->modalDescription(fn () => "{$proposal->client_name} ({$proposal->client_email}) krijgt een e-mail met de link naar dit voorstel. Sla eerst je wijzigingen op.")
                ->modalSubmitActionLabel('Versturen')
                ->action(function () use ($proposal) {
                    try {
                        Mail::to($proposal->client_email)->send(new ProposalSent($proposal));
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->danger()->title('Versturen mislukt')->body('De e-mail kon niet worden verstuurd. Kopieer de link en stuur hem zelf.')->send();

                        return;
                    }

                    $proposal->forceFill(['sent_at' => now()])->save();
                    $proposal->lead?->update(['status' => 'quote']);
                    activity()->causedBy(auth()->user())->performedOn($proposal)->event('sent')->log('proposal_sent');
                    Notification::make()->success()->title('Verstuurd')->body("Verstuurd naar {$proposal->client_email}.")->send();
                }),

            DeleteAction::make(),
        ];
    }

    /** After the client has decided nothing changes any more. */
    protected function getFormActions(): array
    {
        return $this->getRecord()->isDecided() ? [] : parent::getFormActions();
    }

    protected function beforeSave(): void
    {
        abort_if($this->getRecord()->isDecided(), 403);
    }
}
