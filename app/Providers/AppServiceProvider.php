<?php

namespace App\Providers;

use App\Jobs\CheckUptime;
use App\Jobs\RunDueWatches;
use App\Listeners\SanitizeUploadedImage;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\AvailabilityRule;
use App\Models\BlockedDate;
use App\Models\Inquiry;
use App\Models\Lead;
use App\Models\MediaAsset;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PriceItem;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Scan;
use App\Models\ScanWatch;
use App\Models\Service;
use App\Models\Subscriber;
use App\Models\Testimonial;
use App\Models\User;
use App\Policies\ActivityPolicy;
use App\Policies\ContentPolicy;
use App\Policies\InquiryPolicy;
use App\Policies\MediaAssetPolicy;
use App\Policies\OwnerOnlyPolicy;
use App\Policies\PagePolicy;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach ([
            Service::class => ContentPolicy::class,
            Project::class => ContentPolicy::class,
            Testimonial::class => ContentPolicy::class,
            Post::class => ContentPolicy::class,
            PostCategory::class => ContentPolicy::class,
            Page::class => PagePolicy::class,
            MediaAsset::class => MediaAssetPolicy::class,
            Inquiry::class => InquiryPolicy::class,
            NavigationItem::class => OwnerOnlyPolicy::class,
            User::class => OwnerOnlyPolicy::class,
            Subscriber::class => OwnerOnlyPolicy::class,
            Scan::class => OwnerOnlyPolicy::class,
            ScanWatch::class => OwnerOnlyPolicy::class,
            Proposal::class => OwnerOnlyPolicy::class,
            Lead::class => InquiryPolicy::class,
            Appointment::class => InquiryPolicy::class,
            PriceItem::class => OwnerOnlyPolicy::class,
            AppointmentType::class => OwnerOnlyPolicy::class,
            AvailabilityRule::class => OwnerOnlyPolicy::class,
            BlockedDate::class => OwnerOnlyPolicy::class,
            Activity::class => ActivityPolicy::class,
        ] as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Without a scheduler: after a web request has been answered, queue the
        // website-watch runs (hourly) and the uptime measurements (every 10 minutes).
        $this->app->terminating(function () {
            if ($this->app->runningInConsole() || $this->app->runningUnitTests()) {
                return;
            }

            if (Cache::add('scan-watch.tick', true, 3600)) {
                RunDueWatches::dispatch();
            }

            // Live uptime: at most every 10 minutes (every 5 with the scheduler).
            if (Cache::add('uptime.tick', true, 600)) {
                CheckUptime::dispatch();
            }
        });

        // Contact form: 3 a minute and 10 an hour per address.
        RateLimiter::for('contact', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perHour(10)->by($request->ip()),
        ]);

        // Lead funnel forms (calculator, request wizard, appointments): generous for real people, tight for scripts.
        RateLimiter::for('leads', fn (Request $request) => [
            Limit::perMinute(20)->by($request->ip()),
            Limit::perHour(120)->by($request->ip()),
        ]);

        // Website check form: a burst limit; the daily limit per visitor is in ScanController.
        RateLimiter::for('scan', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        Event::listen(MediaHasBeenAddedEvent::class, SanitizeUploadedImage::class);

        // Audit sign-ins and failed attempts.
        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
                activity()->performedOn($event->user)->causedBy($event->user)->event('login')->log('login');
            }
        });

        Event::listen(Failed::class, function (Failed $event) {
            activity()->event('login_failed')->withProperties(['email' => $event->credentials['email'] ?? null, 'ip' => request()->ip()])->log('login_failed');
        });
    }
}
