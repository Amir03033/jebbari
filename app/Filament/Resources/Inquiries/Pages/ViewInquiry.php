<?php

namespace App\Filament\Resources\Inquiries\Pages;

use App\Enums\InquiryStatus;
use App\Filament\Resources\Inquiries\InquiryResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewInquiry extends ViewRecord
{
    protected static string $resource = InquiryResource::class;

    /** Opening an inquiry marks it as read. */
    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->getRecord()->markRead();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')->label('Beantwoorden')->icon('heroicon-o-envelope')
                ->url(fn () => 'mailto:'.$this->getRecord()->email.'?subject='.rawurlencode('Re: uw aanvraag bij Jebbari')),
            Action::make('complete')->label('Afgehandeld')->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn () => $this->getRecord()->status !== InquiryStatus::Completed)
                ->action(function () {
                    $this->getRecord()->markCompleted();
                    Notification::make()->success()->title('Gemarkeerd als afgehandeld')->send();
                }),
            Action::make('reopen')->label('Heropenen')->icon('heroicon-o-arrow-uturn-left')->color('gray')
                ->visible(fn () => $this->getRecord()->status === InquiryStatus::Completed)
                ->action(fn () => $this->getRecord()->update(['status' => InquiryStatus::InProgress, 'completed_at' => null])),
            Action::make('unread')->label('Markeer als ongelezen')->icon('heroicon-o-envelope')->color('gray')
                ->action(function () {
                    $this->getRecord()->update(['read_at' => null, 'status' => InquiryStatus::New]);
                    $this->redirect(InquiryResource::getUrl('index'));
                }),
            Action::make('spam')->label('Spam')->icon('heroicon-o-no-symbol')->color('gray')
                ->visible(fn () => $this->getRecord()->status !== InquiryStatus::Spam)
                ->requiresConfirmation()
                ->action(fn () => $this->getRecord()->update(['status' => InquiryStatus::Spam])),
            DeleteAction::make(),
        ];
    }
}
