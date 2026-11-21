<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LeadPages;
use App\Leads\LeadSession;
use App\Leads\PriceCalculator;
use App\Leads\SpamGuard;
use App\Mail\EstimateMail;
use App\Models\PriceItem;
use App\Support\Locale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The price calculator. Works without JavaScript (a GET form, the server adds
 * it up); with JavaScript the range updates live from the same numbers. Saving
 * always recomputes on the server.
 */
class PriceController extends Controller
{
    use LeadPages;

    public function show(Request $request, PriceCalculator $calculator, LeadSession $leads): View
    {
        $items = $calculator->items();
        $input = $request->query() ?: ($leads->current($request)?->choice('calculator', []) ?? []);
        $result = $calculator->calculate($input);

        return view('pages.price', [
            'seo' => $this->seo('price', 'price'),
            'items' => $items,
            'result' => $result,
            'choices' => $result['choices'] ?? ['project' => null, 'size' => null, 'deadline' => null, 'extras' => []],
            'names' => PriceItem::query()->live()->get()->mapWithKeys(fn ($i) => [$i->key => loc($i->name)])->all(),
            // Plain numbers for the live calculation in the browser.
            'data' => $items->flatten()->map(fn (PriceItem $i) => ['key' => $i->key, 'group' => $i->group, 'min' => $i->price_min, 'max' => $i->price_max])->values()->all(),
        ]);
    }

    /** "Request a quote", "Book a call" or "Email me this": store the choices on the visitor's lead first. */
    public function save(Request $request, PriceCalculator $calculator, LeadSession $leads, SpamGuard $guard): RedirectResponse
    {
        $next = $request->input('next');
        $result = $calculator->calculate($request->only('project', 'size', 'deadline', 'extras'));

        if ($result === null) {
            return redirect(Locale::url('price'))->withErrors(['project' => __('leads.price.choose_project')]);
        }

        $back = redirect(Locale::url('price').'?'.http_build_query($result['choices']).'#estimate');

        if ($next === 'email' && SpamGuard::isBot($request)) {
            return $back->with('estimate_sent', true);
        }

        if ($next === 'email') {
            $request->validateWithBag('estimate', [
                'email' => ['required', 'email:rfc', 'max:190'],
                'privacy' => ['accepted'],
            ], ['privacy.accepted' => __('leads.form.privacy_required')], ['email' => __('leads.form.email')]);
            $guard->check($request, 'email');
        }

        $lead = $leads->currentOrCreate($request, 'calculator');
        $lead->setChoice('calculator', $result['choices']);
        $lead->fill(['estimate_min' => $result['min'], 'estimate_max' => $result['max']]);

        if ($next === 'email') {
            $lead->fill(['email' => mb_strtolower(trim($request->string('email'))), 'contact_consent' => true]);
        }

        // The wizard starts on what was chosen here.
        if (! $lead->choice('wizard.project')) {
            $lead->setChoice('wizard.project', $result['choices']['project']);
        }

        $lead->save();

        if ($next === 'email') {
            $names = PriceItem::query()->whereIn('key', array_column($result['lines'], 'key'))->get()->keyBy('key');
            $lines = array_map(fn ($l) => $l + ['name' => loc($names[$l['key']]->name ?? null) ?? $l['key']], $result['lines']);
            $this->mailVisitor($lead->email, new EstimateMail($lead, $lines));
            $this->notifyOwner($lead, 'estimate');

            return $back->with('estimate_sent', true);
        }

        return redirect($next === 'appointment' ? Locale::url('appointment') : Locale::route('request.step', ['step' => 1]));
    }
}
