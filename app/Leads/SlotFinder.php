<?php

namespace App\Leads;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\AvailabilityRule;
use App\Models\BlockedDate;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * Free appointment slots: opening hours (per weekday, Europe/Amsterdam) minus
 * blocked days, minus confirmed appointments with a buffer around them, minus
 * anything sooner than the minimum notice. Built on local wall-clock times,
 * so 10:00 stays 10:00 across summer and winter time; returned in UTC.
 */
class SlotFinder
{
    /** @return array<string, list<CarbonImmutable>> local date (Y-m-d) => slot starts (UTC), only days with slots */
    public function days(AppointmentType $type, ?CarbonImmutable $now = null): array
    {
        $tz = config('site.timezone');
        $now ??= CarbonImmutable::now();
        $earliest = $now->addHours((int) config('leads.min_notice_hours'));
        $today = $now->timezone($tz)->startOfDay();
        $last = $today->addDays((int) config('leads.horizon_days'));

        $rules = AvailabilityRule::query()
            ->where(fn ($q) => $q->whereNull('appointment_type_id')->orWhere('appointment_type_id', $type->id))
            ->get()->groupBy('weekday');
        $blocked = BlockedDate::query()->whereBetween('date', [$today->toDateString(), $last->toDateString()])->pluck('date')
            ->map(fn ($d) => $d->toDateString())->flip();
        $busy = $this->busy($today->utc(), $last->addDay()->utc());

        $step = (int) config('leads.slot_step_minutes');
        $length = $type->duration_minutes;
        $days = [];

        foreach (CarbonPeriod::create($today, $last) as $day) {
            $date = $day->toDateString();

            if (isset($blocked[$date]) || ! $rules->has($day->isoWeekday())) {
                continue;
            }

            $slots = [];
            foreach ($rules[$day->isoWeekday()] as $rule) {
                $open = CarbonImmutable::parse($date.' '.$rule->starts_at, $tz);
                $close = CarbonImmutable::parse($date.' '.$rule->ends_at, $tz);

                for ($start = $open; $start->addMinutes($length)->lte($close); $start = $start->addMinutes($step)) {
                    $end = $start->addMinutes($length);

                    if ($start->lt($earliest) || $this->overlaps($start, $end, $busy)) {
                        continue;
                    }

                    $slots[Appointment::slotKey($start)] = $start->utc();
                }
            }

            if ($slots) {
                ksort($slots);
                $days[$date] = array_values($slots);
            }
        }

        return $days;
    }

    public function isFree(AppointmentType $type, CarbonImmutable $start): bool
    {
        foreach ($this->days($type)[$start->timezone(config('site.timezone'))->toDateString()] ?? [] as $slot) {
            if ($slot->equalTo($start)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<array{0: CarbonImmutable, 1: CarbonImmutable}> confirmed appointments widened by the buffer */
    public function busy(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $buffer = (int) config('leads.buffer_minutes');

        return Appointment::query()->confirmed()
            ->where('starts_at', '<', $to->addMinutes($buffer))
            ->where('ends_at', '>', $from->subMinutes($buffer))
            ->get(['starts_at', 'ends_at'])
            ->map(fn ($a) => [CarbonImmutable::parse($a->starts_at)->subMinutes($buffer), CarbonImmutable::parse($a->ends_at)->addMinutes($buffer)])
            ->all();
    }

    private function overlaps(CarbonImmutable $start, CarbonImmutable $end, array $busy): bool
    {
        foreach ($busy as [$from, $to]) {
            if ($start->lt($to) && $end->gt($from)) {
                return true;
            }
        }

        return false;
    }
}
