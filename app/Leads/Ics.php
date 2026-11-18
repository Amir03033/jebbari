<?php

namespace App\Leads;

use App\Models\Appointment;

/** An iCalendar (.ics) file for one appointment, so it can be added to any calendar. */
final class Ics
{
    public static function forAppointment(Appointment $appointment, string $summary, string $description, ?string $location, ?string $organizer, bool $cancelled = false): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//jebbari.nl//Afspraak//NL',
            'CALSCALE:GREGORIAN',
            'METHOD:'.($cancelled ? 'CANCEL' : 'PUBLISH'),
            'BEGIN:VEVENT',
            'UID:'.$appointment->token.'@'.(parse_url(config('app.url'), PHP_URL_HOST) ?: 'jebbari.nl'),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$appointment->starts_at->copy()->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$appointment->ends_at->copy()->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.self::escape($summary),
            'DESCRIPTION:'.self::escape($description),
            $location ? 'LOCATION:'.self::escape($location) : null,
            $organizer ? 'ORGANIZER;CN=Jebbari:mailto:'.$organizer : null,
            'STATUS:'.($cancelled ? 'CANCELLED' : 'CONFIRMED'),
            'SEQUENCE:'.($cancelled ? 1 : 0),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_map(self::fold(...), array_filter($lines)))."\r\n";
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $text);
    }

    /** Lines longer than 75 octets continue on the next line after a space (RFC 5545). */
    private static function fold(string $line): string
    {
        $out = '';
        while (strlen($line) > 75) {
            $cut = 75;
            // Do not split a multi-byte character.
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--;
            }
            $out .= substr($line, 0, $cut)."\r\n ";
            $line = substr($line, $cut);
        }

        return $out.$line;
    }
}
