<?php
namespace Modules\Stories\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;

class StoriesServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Stories';
    protected string $nameLower = 'stories';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
