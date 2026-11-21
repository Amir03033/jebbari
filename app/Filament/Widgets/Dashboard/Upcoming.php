<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Resources\AvailabilityRules\AvailabilityRuleResource;
use App\Models\Appointment;
use Filament\Widgets\Widget;

/** The next five confirmed appointments. */
class Upcoming extends Widget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected string $view = 'filament.dashboard.upcoming';

    protected int|string|array $columnSpan = ['default' => 'full', 'lg' => 1];

    public static function canView(): bool
    {
        return auth()->user()?->canManageInbox() ?? false;
    }

    protected function getViewData(): array
    {
        $appointments = Appointment::query()->confirmed()->where('starts_at', '>', now())->with('lead', 'type')->orderBy('starts_at')->limit(5)->get();

        return [
            'appointments' => $appointments->map(fn (Appointment $a) => [
                'day' => $a->localStart()->locale('nl')->translatedFormat('D'),
                'date' => $a->localStart()->format('j'),
                'month' => $a->localStart()->locale('nl')->translatedFormat('M'),
                'time' => $a->localStart()->format('H:i'),
                'name' => $a->lead?->name ?: $a->lead?->email ?: 'Onbekend',
                'type' => loc($a->type?->name) ?? '',
            ])->all(),
            'indexUrl' => AppointmentResource::getUrl('index'),
            'hoursUrl' => auth()->user()?->isOwner() ? AvailabilityRuleResource::getUrl('index') : null,
        ];
    }
}
