<?php

namespace App\Filament\Resources\Leads\Pages;

use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Proposals\ProposalResource;
use App\Models\Lead;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')->label('Mailen')->icon('heroicon-o-envelope')
                ->visible(fn () => filled($this->getRecord()->email))
                ->url(fn () => 'mailto:'.$this->getRecord()->email),
            Action::make('proposal')->label('Maak voorstel')->icon('heroicon-o-document-text')
                ->visible(fn () => filled($this->getRecord()->email) && auth()->user()?->isOwner())
                ->url(fn () => ProposalResource::getUrl('create', ['lead' => $this->getRecord()->id])),
            Action::make('update')->label('Status en notities')->icon('heroicon-o-pencil-square')
                ->fillForm(fn () => ['status' => $this->getRecord()->status, 'notes' => $this->getRecord()->notes])
                ->schema([
                    Select::make('status')->label('Status')->options(Lead::STATUSES)->required(),
                    Textarea::make('notes')->label('Notities')->rows(6)->maxLength(10000),
                ])
                ->action(function (array $data) {
                    $this->getRecord()->update(['status' => $data['status'], 'notes' => $data['notes']]);
                    Notification::make()->success()->title('Opgeslagen')->send();
                }),
            DeleteAction::make(),
        ];
    }
}
