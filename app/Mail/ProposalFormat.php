<?php

namespace App\Mail;

use App\Leads\PriceCalculator;
use App\Models\Proposal;

/** The key facts of a proposal as label => value rows, for mails. */
class ProposalFormat
{
    /** @return array<string, string> */
    public static function rows(Proposal $proposal, string $locale): array
    {
        $rows = [__('proposal.label', [], $locale) => $proposal->title];

        foreach ($proposal->totals() as $period => $sum) {
            $rows[__('proposal.total', [], $locale).' ('.__("proposal.period.$period", [], $locale).')'] = PriceCalculator::money($sum);
        }

        if ($proposal->valid_until) {
            $rows[__('proposal.valid_label', [], $locale)] = $proposal->valid_until->locale($locale)->translatedFormat('j F Y');
        }

        return $rows;
    }
}
