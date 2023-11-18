<?php

namespace App\Providers;

use App\Models\CMS\News;
use App\Models\Employee\EmployeeReport;
use App\Models\Employee\EmployeeRequest;
use App\Observers\Employee\EmployeeReportObserver;
use App\Observers\Employee\EmployeeRequestObserver;
use App\Observers\NewsObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        News::observe(NewsObserver::class);
        EmployeeReport::observe(EmployeeReportObserver::class);
        EmployeeRequest::observe(EmployeeRequestObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
