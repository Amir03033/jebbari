<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Pages\Settings\SiteStatus;
use App\Filament\Resources\Newsletters\NewsletterResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Proposals\ProposalResource;
use App\Support\Locale;
use App\Support\SiteOffline;
use Filament\Widgets\Widget;

/** Greeting, the site's state and the actions used most. */
class Welcome extends Widget
{
    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected string $view = 'filament.dashboard.welcome';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $user = auth()->user();
        $hour = (int) now(config('site.timezone'))->format('G');

        $actions = array_values(array_filter([
            $user?->isOwner() ? ['label' => 'Nieuw voorstel', 'icon' => 'heroicon-m-document-text', 'url' => ProposalResource::getUrl('create')] : null,
            $user?->canEditContent() ? ['label' => 'Nieuw artikel', 'icon' => 'heroicon-m-pencil-square', 'url' => PostResource::getUrl('create')] : null,
            $user?->isOwner() ? ['label' => 'Nieuwsbrief', 'icon' => 'heroicon-m-paper-airplane', 'url' => NewsletterResource::getUrl('create')] : null,
        ]));

        return [
            'greeting' => match (true) {
                $hour < 6 => 'Goedenacht',
                $hour < 12 => 'Goedemorgen',
                $hour < 18 => 'Goedemiddag',
                default => 'Goedenavond',
            },
            'name' => strtok((string) $user?->name, ' ') ?: '',
            'date' => now(config('site.timezone'))->locale('nl')->translatedFormat('l j F'),
            'offline' => SiteOffline::isOn(),
            'statusUrl' => $user?->isOwner() ? SiteStatus::getUrl() : null,
            'siteUrl' => Locale::url('home', 'nl'),
            'actions' => $actions,
        ];
    }
}
