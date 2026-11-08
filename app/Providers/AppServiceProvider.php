<?php

namespace App\Providers;

use App\Listeners\SanitizeUploadedImage;
use App\Models\Inquiry;
use App\Models\MediaAsset;
use App\Models\NavigationItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Project;
use App\Models\Service;
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
            Activity::class => ActivityPolicy::class,
        ] as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Contact form: 3 a minute and 10 an hour per address.
        RateLimiter::for('contact', fn (Request $request) => [
            Limit::perMinute(3)->by($request->ip()),
            Limit::perHour(10)->by($request->ip()),
        ]);

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
