<?php

namespace App\Leads;

use App\Models\Appointment;
use App\Models\Lead;
use Illuminate\Http\Request;

/**
 * The visitor's one lead, shared by the calculator, the wizard and the
 * appointment flow through a token in the session (and in resume links), so
 * details are entered once.
 */
class LeadSession
{
    public const KEY = 'lead_token';

    public function current(Request $request): ?Lead
    {
        $token = $request->session()->get(self::KEY);

        return $token ? Lead::query()->where('token', $token)->first() : null;
    }

    /** The current lead, or a new one started from $source. */
    public function currentOrCreate(Request $request, string $source): Lead
    {
        $lead = $this->current($request);

        if ($lead === null || $lead->submitted_at !== null && $source !== 'appointment') {
            // A request that was sent stays as it is: a new journey starts a new lead.
            $lead = Lead::create([
                'source' => $source,
                'locale' => app()->getLocale(),
                'ip_hash' => self::ipHash($request),
            ]);
            $this->remember($request, $lead);
            self::prune();
        }

        return $lead;
    }

    public function remember(Request $request, Lead $lead): void
    {
        $request->session()->put(self::KEY, $lead->token);
    }

    public function forget(Request $request): void
    {
        $request->session()->forget(self::KEY);
    }

    public static function ipHash(Request $request): string
    {
        return hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
    }

    /** Housekeeping without a scheduler: anonymous drafts and long-past appointments go when new leads start. */
    public static function prune(): void
    {
        (new Lead)->prunable()->delete();
        (new Appointment)->prunable()->delete();
    }
}
