<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Pages\Settings\SiteStatus;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Proposals\ProposalResource;
use App\Filament\Resources\Services\ServiceResource;
use App\Models\Inquiry;
use App\Models\Lead;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Service;
use App\Support\SiteOffline;
use Filament\Widgets\Widget;

/** A short to-do list built from the data: only what needs a decision or an answer. */
class Attention extends Widget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected string $view = 'filament.dashboard.attention';

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 2];

    protected function getViewData(): array
    {
        $user = auth()->user();
        $items = [];
        $add = function (int $count, string $tone, string $icon, string $label, string $hint, string $url) use (&$items) {
            if ($count > 0) {
                $items[] = compact('count', 'tone', 'icon', 'label', 'hint', 'url');
            }
        };

        if ($user?->isOwner() && SiteOffline::isOn()) {
            $add(1, 'danger', 'heroicon-o-power', 'De website is offline', 'Bezoekers zien de offline-pagina', SiteStatus::getUrl());
        }

        if ($user?->canManageInbox()) {
            $add(Inquiry::query()->whereNull('read_at')->count(), 'warning', 'heroicon-o-envelope', 'Ongelezen berichten', 'Via het contactformulier', InquiryResource::getUrl('index'));
            $add(Lead::query()->where('status', 'new')->whereNotNull('email')->count(), 'warning', 'heroicon-o-user-plus', 'Nieuwe leads', 'Aanvragen, prijscalculator en website-checks', LeadResource::getUrl('index'));
        }

        if ($user?->isOwner()) {
            $open = Proposal::query()->whereNull('accepted_at')->whereNull('declined_at')->get();
            $add($open->filter(fn ($p) => $p->isOpen() && $p->first_viewed_at && $p->first_viewed_at->lt(now()->subDays(3)))->count(), 'info', 'heroicon-o-eye', 'Voorstellen bekeken, nog geen besluit', 'Meer dan 3 dagen geleden geopend: even navragen?', ProposalResource::getUrl('index'));
            $add($open->filter(fn ($p) => ! $p->sent_at && ! $p->isExpired())->count(), 'gray', 'heroicon-o-document-text', 'Voorstellen nog niet verstuurd', 'Klaar om te versturen?', ProposalResource::getUrl('index'));
            $add($open->filter(fn ($p) => $p->sent_at && $p->isExpired())->count(), 'gray', 'heroicon-o-clock', 'Verlopen voorstellen', 'Opnieuw aanbieden of afsluiten', ProposalResource::getUrl('index'));
        }

        if ($user?->canEditContent()) {
            foreach ([
                [Page::class, PageResource::class, 'pagina'],
                [Service::class, ServiceResource::class, 'dienst'],
                [Project::class, ProjectResource::class, 'project'],
                [Post::class, PostResource::class, 'artikel'],
            ] as [$model, $resource, $noun]) {
                $records = $model::query()->get();
                $changes = $records->filter(fn ($r) => $r->isPublished() && $r->hasDraftChanges())->count();
                $add($changes, 'info', 'heroicon-o-arrow-up-tray', "Niet-gepubliceerde wijzigingen ($noun)", 'Bekijk en publiceer', $resource::getUrl('index'));
            }
        }

        return ['items' => $items];
    }
}
