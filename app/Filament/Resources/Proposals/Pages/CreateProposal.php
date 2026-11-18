<?php

namespace App\Filament\Resources\Proposals\Pages;

use App\Filament\Resources\Proposals\ProposalResource;
use App\Models\Lead;
use Filament\Resources\Pages\CreateRecord;

class CreateProposal extends CreateRecord
{
    protected static string $resource = ProposalResource::class;

    /** "Maak voorstel" on a lead opens this page with ?lead=ID: start from that lead. */
    protected function fillForm(): void
    {
        parent::fillForm();

        if ($lead = Lead::find(request()->integer('lead'))) {
            $this->form->fill([
                'lead_id' => $lead->id,
                'client_name' => $lead->name,
                'client_email' => $lead->email,
                'client_company' => $lead->company,
                'locale' => $lead->locale,
                'valid_until' => now()->addDays(30)->toDateString(),
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
