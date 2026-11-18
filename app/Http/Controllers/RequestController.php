<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\LeadPages;
use App\Http\Requests\StoreInquiryRequest;
use App\Leads\LeadSession;
use App\Leads\PriceCalculator;
use App\Leads\SpamGuard;
use App\Mail\RequestReceived;
use App\Models\Lead;
use App\Models\PriceItem;
use App\Support\Locale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The request wizard: five short steps instead of one long form. Every step
 * is saved on the lead, so leaving halfway loses nothing; the resume link
 * (token) brings the visitor back. Plain forms, no JavaScript needed.
 */
class RequestController extends Controller
{
    use LeadPages;

    public const DEADLINES = ['asap', '1-3m', '3-6m', 'flexible'];

    public function start(Request $request, LeadSession $leads): RedirectResponse
    {
        $lead = $leads->current($request);

        return redirect(Locale::route('request.step', ['step' => $lead && ! $lead->submitted_at ? $lead->wizard_step : 1]));
    }

    public function resume(Request $request, LeadSession $leads, string $lead): RedirectResponse
    {
        $found = Lead::query()->where('token', $lead)->firstOrFail();
        $leads->remember($request, $found);

        return redirect(Locale::route($found->submitted_at ? 'request.done' : 'request.step', $found->submitted_at ? [] : ['step' => $found->wizard_step]));
    }

    public function step(Request $request, LeadSession $leads, int $step): View|RedirectResponse
    {
        $lead = $leads->current($request);

        if ($lead?->submitted_at) {
            $lead = null;
        }

        // No skipping ahead: a step opens once the ones before it are done.
        $reached = $lead?->wizard_step ?? 1;

        if ($step > $reached) {
            return redirect(Locale::route('request.step', ['step' => $reached]));
        }

        return view('pages.request', [
            'seo' => $this->seo('request', 'request', false),
            'step' => $step,
            'lead' => $lead,
            'w' => $lead?->choice('wizard', []) ?? [],
            'projects' => PriceItem::query()->live()->where('group', 'project')->get(),
            'calculator' => $lead?->choice('calculator'),
            'estimate' => PriceCalculator::range($lead?->estimate_min, $lead?->estimate_max),
            'names' => PriceItem::query()->live()->get()->mapWithKeys(fn ($i) => [$i->key => loc($i->name)])->all(),
        ]);
    }

    public function save(Request $request, LeadSession $leads, SpamGuard $guard, int $step): RedirectResponse
    {
        if ($step === Lead::WIZARD_STEPS && SpamGuard::isBot($request)) {
            return redirect(Locale::route('request.done'));
        }

        $data = $request->validate($this->rules($step), ['privacy.accepted' => __('leads.form.privacy_required')], $this->attributes());

        if ($step === Lead::WIZARD_STEPS) {
            $guard->check($request, 'name');
        }

        $lead = $leads->currentOrCreate($request, 'wizard');

        match ($step) {
            1 => $this->put($lead, ['project' => $data['project'] ?? null, 'summary' => trim($data['summary'])]),
            2 => $this->put($lead, ['goal' => trim((string) ($data['goal'] ?? '')), 'audience' => trim((string) ($data['audience'] ?? ''))]),
            3 => $this->put($lead, ['budget' => $data['budget'] ?? null, 'deadline' => $data['deadline'] ?? null]),
            4 => $this->put($lead, ['features' => trim((string) ($data['features'] ?? '')), 'examples' => array_values(array_filter(array_map('trim', $data['examples'] ?? [])))]),
            5 => $lead->fill([
                'name' => trim($data['name']),
                'email' => mb_strtolower(trim($data['email'])),
                'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
                'company' => trim((string) ($data['company'] ?? '')) ?: null,
                'contact_consent' => true,
            ]),
        };

        $lead->wizard_step = max($lead->wizard_step, min($step + 1, Lead::WIZARD_STEPS));

        if ($step < Lead::WIZARD_STEPS) {
            $lead->save();

            return redirect(Locale::route('request.step', ['step' => $step + 1]));
        }

        $lead->fill(['submitted_at' => now(), 'status' => 'new'])->save();
        $this->mailVisitor($lead->email, new RequestReceived($lead));
        $this->notifyOwner($lead, 'request');

        return redirect(Locale::route('request.done'));
    }

    public function done(Request $request, LeadSession $leads): View
    {
        return view('pages.request-done', ['seo' => $this->seo('request', 'request', false), 'lead' => $leads->current($request)]);
    }

    private function put(Lead $lead, array $values): void
    {
        foreach ($values as $key => $value) {
            $lead->setChoice('wizard.'.$key, $value);
        }
    }

    private function rules(int $step): array
    {
        $projects = PriceItem::query()->live()->where('group', 'project')->pluck('key')->push('other')->all();

        return match ($step) {
            1 => ['project' => ['nullable', 'string', 'in:'.implode(',', $projects)], 'summary' => ['required', 'string', 'min:10', 'max:3000']],
            2 => ['goal' => ['nullable', 'string', 'max:2000'], 'audience' => ['nullable', 'string', 'max:2000']],
            3 => ['budget' => ['nullable', 'in:'.implode(',', StoreInquiryRequest::BUDGETS)], 'deadline' => ['nullable', 'in:'.implode(',', self::DEADLINES)]],
            4 => ['features' => ['nullable', 'string', 'max:3000'], 'examples' => ['nullable', 'array', 'max:3'], 'examples.*' => ['nullable', 'url:http,https', 'max:500']],
            5 => [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email:rfc', 'max:190'],
                'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9 +()\-]{6,40}$/'],
                'company' => ['nullable', 'string', 'max:160'],
                'privacy' => ['accepted'],
                'website' => ['nullable', 'string', 'max:255'],
                '_t' => ['required', 'string'],
            ],
            default => abort(404),
        };
    }

    private function attributes(): array
    {
        return [
            'summary' => __('leads.request.summary'), 'goal' => __('leads.request.goal'), 'audience' => __('leads.request.audience'),
            'features' => __('leads.request.features'), 'examples.*' => __('leads.request.examples'),
            'name' => __('leads.form.name'), 'email' => __('leads.form.email'), 'phone' => __('leads.form.phone'), 'company' => __('leads.form.company'),
        ];
    }
}
