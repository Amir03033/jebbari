<?php

namespace App\Http\Controllers;

use App\Content\Site;
use App\Leads\LeadSession;
use App\Mail\ProposalAccepted;
use App\Mail\ProposalDecision;
use App\Models\Proposal;
use App\Support\Seo;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The client's side of a proposal: read it, accept it with their name, or
 * decline it. Private: a 40-character token, noindex, never cached.
 * Views by signed-in team members are not counted.
 */
class ProposalController extends Controller
{
    public function show(Request $request, Proposal $proposal): Response
    {
        if (! $request->user()) {
            $proposal->forceFill([
                'first_viewed_at' => $proposal->first_viewed_at ?? now(),
                'last_viewed_at' => now(),
                'view_count' => $proposal->view_count + 1,
            ])->save();
        }

        $seo = Seo::make([
            'title' => __('proposal.meta_title').': '.$proposal->title,
            'description' => __('proposal.for', ['name' => $proposal->client_company ?: $proposal->client_name]),
            'canonical' => $proposal->url(),
            'noindex' => true,
        ]);

        return response(view('pages.proposal', [
            'seo' => $seo,
            'proposal' => $proposal,
            'termsUrl' => Site::pageUrl('terms'),
            'email' => Settings::get('contact.email'),
        ]))->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function accept(Request $request, Proposal $proposal): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'agree' => ['accepted'],
        ]);

        if ($proposal->isOpen()) {
            $proposal->forceFill([
                'accepted_at' => now(),
                'accepted_name' => trim($data['name']),
                'accepted_ip_hash' => LeadSession::ipHash($request),
            ])->save();

            $proposal->lead?->update(['status' => 'won']);

            $this->mail($proposal->client_email, new ProposalAccepted($proposal));
            $this->mail($this->ownerEmail(), new ProposalDecision($proposal));

            session()->flash('proposal', 'accepted');
        }

        return redirect($proposal->url());
    }

    public function decline(Request $request, Proposal $proposal): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);

        if ($proposal->isOpen()) {
            $proposal->forceFill(['declined_at' => now(), 'decline_reason' => $data['reason'] ?? null])->save();
            $proposal->lead?->update(['status' => 'lost']);

            $this->mail($this->ownerEmail(), new ProposalDecision($proposal));

            session()->flash('proposal', 'declined');
        }

        return redirect($proposal->url());
    }

    private function ownerEmail(): ?string
    {
        return Settings::get('contact.notify_email') ?: Settings::get('contact.email');
    }

    private function mail(?string $to, $mailable): void
    {
        if (! $to) {
            return;
        }

        try {
            Mail::to($to)->send($mailable);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
