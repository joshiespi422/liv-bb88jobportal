<?php
namespace App\Providers;

use App\Events\TaskCreated;
use App\Listeners\SendNewTaskNotification;
use App\Events\AccomplishmentCreated;
use App\Listeners\SendAccomplishmentNotification;
use App\Events\ProjectCreated;
use App\Listeners\SendProjectNotification;
use App\Events\TaskValidated;
use App\Listeners\SendTaskValidatedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Add event and listener here
        TaskCreated::class => [
            SendNewTaskNotification::class,
        ],
        AccomplishmentCreated::class => [
            SendAccomplishmentNotification::class,
        ],
        ProjectCreated::class => [
            SendProjectNotification::class
        ],
        TaskValidated::class => [
            SendTaskValidatedNotification::class
        ]        
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}