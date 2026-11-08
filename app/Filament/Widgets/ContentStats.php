<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Inquiries\InquiryResource;
use App\Models\Inquiry;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Models\Service;
use App\Models\Testimonial;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Overzicht';

    private const MODELS = [Page::class, Service::class, Project::class, Testimonial::class, Post::class];

    protected function getStats(): array
    {
        $user = auth()->user();
        $stats = [];

        $unread = Inquiry::query()->whereNull('read_at')->count();
        $stats[] = Stat::make('Ongelezen aanvragen', $unread)
            ->description($unread ? 'Bekijk je inbox' : 'Alles gelezen')
            ->color($unread ? 'warning' : 'success')
            ->url(InquiryResource::getUrl('index'));

        if ($user?->canEditContent()) {
            $records = collect(self::MODELS)->flatMap(fn ($m) => $m::query()->get());

            $stats[] = Stat::make('Concepten', $records->filter(fn ($r) => ! $r->isPublished())->count())
                ->description('Nog niet gepubliceerd');
            $stats[] = Stat::make('Niet-gepubliceerde wijzigingen', $records->filter(fn ($r) => $r->isPublished() && $r->hasDraftChanges())->count())
                ->description('Live versie is ongewijzigd')->color('warning');
            $stats[] = Stat::make('Onvolledige vertalingen', $records->reject(fn ($r) => $r->isFullyTranslated())->count())
                ->description('Publiceren is dan geblokkeerd')->color('warning');
        }

        return $stats;
    }
}
