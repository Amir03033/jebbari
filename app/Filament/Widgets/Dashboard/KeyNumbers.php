<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Proposals\ProposalResource;
use App\Models\Appointment;
use App\Models\Inquiry;
use App\Models\Lead;
use App\Models\PageView;
use App\Models\Proposal;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Four numbers with a two-week line each: visits, new requests, appointments, open proposals. */
class KeyNumbers extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $tz = config('site.timezone');
        $days = collect(range(13, 0))->map(fn ($i) => CarbonImmutable::now($tz)->subDays($i)->toDateString());

        // Visits: this week against last week.
        $views = PageView::query()->where('date', '>=', $days->first())->selectRaw('date, SUM(views) as v')->groupBy('date')->pluck('v', 'date')
            ->mapWithKeys(fn ($v, $d) => [substr((string) $d, 0, 10) => (int) $v]);
        $series = $days->map(fn ($d) => (int) ($views[$d] ?? 0));
        $thisWeek = $series->slice(7)->sum();
        $lastWeek = $series->take(7)->sum();
        $change = $lastWeek > 0 ? (int) round(($thisWeek - $lastWeek) / $lastWeek * 100) : null;

        $stats = [
            Stat::make('Bezoek, 7 dagen', number_format($thisWeek, 0, ',', '.'))
                ->description($change === null ? 'Paginaweergaven, zonder cookies' : ($change >= 0 ? "+$change% t.o.v. vorige week" : "$change% t.o.v. vorige week"))
                ->descriptionIcon($change !== null && $change < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($change !== null && $change < 0 ? 'gray' : 'success')
                ->chart($series->all()),
        ];

        if ($user?->canManageInbox()) {
            $since = CarbonImmutable::now($tz)->subDays(13)->startOfDay()->utc();
            $perDay = fn ($rows) => $rows->groupBy(fn ($at) => $at->timezone($tz)->toDateString())->map->count();
            $inq = $perDay(Inquiry::query()->where('created_at', '>=', $since)->pluck('created_at'));
            $leads = $perDay(Lead::query()->whereNotNull('submitted_at')->where('submitted_at', '>=', $since)->pluck('submitted_at'));
            $requests = $days->map(fn ($d) => ($inq[$d] ?? 0) + ($leads[$d] ?? 0));
            $open = Lead::query()->where('status', 'new')->whereNotNull('email')->count() + Inquiry::query()->whereNull('read_at')->count();

            $stats[] = Stat::make('Nieuwe aanvragen, 14 dagen', $requests->sum())
                ->description($open ? "$open nog niet opgepakt" : 'Alles opgepakt')
                ->color($open ? 'warning' : 'success')
                ->chart($requests->all())
                ->url($open && Lead::query()->where('status', 'new')->exists() ? LeadResource::getUrl('index') : InquiryResource::getUrl('index'));

            $week = Appointment::query()->confirmed()->whereBetween('starts_at', [now(), now()->addDays(7)])->count();
            $next = Appointment::query()->confirmed()->where('starts_at', '>', now())->orderBy('starts_at')->first();

            $stats[] = Stat::make('Afspraken, komende 7 dagen', $week)
                ->description($next ? 'Eerstvolgende: '.$next->localStart()->locale('nl')->translatedFormat('D j M, H:i') : 'Geen afspraken gepland')
                ->url(AppointmentResource::getUrl('index'));
        }

        if ($user?->isOwner()) {
            $open = Proposal::query()->whereNull('accepted_at')->whereNull('declined_at')->whereNotNull('sent_at')->get()->filter->isOpen();
            $won = Proposal::query()->where('accepted_at', '>=', now()->subDays(90))->get();

            $stats[] = Stat::make('Open voorstellen', $open->count())
                ->description($open->count()
                    ? $open->whereNotNull('first_viewed_at')->count().' bekeken, '.$open->whereNull('first_viewed_at')->count().' nog niet'
                    : ($won->count() ? $won->count().' akkoord in 90 dagen' : 'Geen openstaande voorstellen'))
                ->color($open->count() ? 'info' : 'gray')
                ->url(ProposalResource::getUrl('index'));
        }

        return $stats;
    }
}
