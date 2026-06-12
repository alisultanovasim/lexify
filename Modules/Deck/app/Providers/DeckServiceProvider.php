<?php
namespace Modules\Deck\Providers;

use Illuminate\Support\Facades\Gate;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Modules\Deck\Models\Deck;
use Modules\Deck\Policies\DeckPolicy;

class DeckServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Deck';
    protected string $nameLower = 'deck';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Deck::class, DeckPolicy::class);
    }
}
