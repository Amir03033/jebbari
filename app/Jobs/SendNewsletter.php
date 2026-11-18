<?php

namespace App\Jobs;

use App\Mail\NewsletterMail;
use App\Models\Newsletter;
use App\Models\Subscriber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** One newsletter to one subscriber. Queued per address, so one bad address never stops the rest. */
class SendNewsletter implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public Newsletter $newsletter, public Subscriber $subscriber) {}

    public function handle(): void
    {
        $subscriber = $this->subscriber->fresh();

        // Unsubscribed in the meantime: send nothing.
        if (! $subscriber || ! $subscriber->confirmed_at || $subscriber->unsubscribed_at) {
            return;
        }

        try {
            Mail::to($subscriber->email)->send(new NewsletterMail($this->newsletter, $subscriber, $subscriber->locale ?: 'nl'));
        } catch (Throwable $e) {
            report($e);

            throw $e;
        }
    }

    /** Queue one mail per confirmed subscriber and mark the newsletter as sent. Returns the number of recipients. */
    public static function toEveryone(Newsletter $newsletter): int
    {
        $count = 0;

        Subscriber::query()->active()->orderBy('id')->each(function (Subscriber $subscriber) use ($newsletter, &$count) {
            self::dispatch($newsletter, $subscriber);
            $count++;
        });

        $newsletter->forceFill(['sent_at' => now(), 'recipients' => $count])->save();

        return $count;
    }
}
