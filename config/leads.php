<?php

/*
 * Price calculator, request wizard and appointment booking. See docs/LEADS.md.
 * Prices, appointment types, opening hours and blocked days live in the admin.
 */
return [
    // Appointment slots.
    'slot_step_minutes' => 30,      // a slot can start every 30 minutes within an availability rule
    'buffer_minutes' => 15,         // free time kept before and after every appointment
    'min_notice_hours' => 24,       // no bookings sooner than this
    'horizon_days' => 21,           // how far ahead visitors can book
    'reminder_hours' => 24,         // reminder mail this long before the appointment

    // Housekeeping.
    'draft_days' => 30,             // unfinished requests without contact details are deleted after this
    'appointment_days' => 365,      // appointments are deleted this long after they ended
];
