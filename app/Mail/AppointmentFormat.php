<?php

namespace App\Mail;

use App\Models\Appointment;

/** How an appointment reads in mails and on pages: date, time, place. */
final class AppointmentFormat
{
    public static function when(Appointment $a, string $locale): string
    {
        $start = $a->localStart()->locale($locale);

        return $start->translatedFormat('l j F, H:i');
    }

    public static function place(Appointment $a, string $locale): ?string
    {
        return loc($a->type->location, $locale) ?? ($a->type->is_online ? __('leads.appointment.online', [], $locale) : null);
    }

    /** @return array<string, string> */
    public static function rows(Appointment $a, string $locale): array
    {
        $start = $a->localStart()->locale($locale);

        return array_filter([
            __('leads.appointment.what', [], $locale) => loc($a->type->name, $locale),
            __('leads.appointment.date', [], $locale) => $start->translatedFormat('l j F Y'),
            __('leads.appointment.time', [], $locale) => $start->format('H:i').' – '.$a->ends_at->copy()->timezone(config('site.timezone'))->format('H:i'),
            __('leads.appointment.where', [], $locale) => self::place($a, $locale),
        ]);
    }
}
