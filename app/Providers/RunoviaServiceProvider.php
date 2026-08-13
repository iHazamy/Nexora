<?php

declare(strict_types=1);

namespace App\Providers;

use App\Runovia\ApiSession;
use App\Runovia\Resources\ActivityLogApi;
use App\Runovia\Resources\AuthApi;
use App\Runovia\Resources\BankAccountApi;
use App\Runovia\Resources\BusinessApi;
use App\Runovia\Resources\CustomerApi;
use App\Runovia\Resources\InvoiceApi;
use App\Runovia\Resources\PackageApi;
use App\Runovia\Resources\PaymentApi;
use App\Runovia\Resources\PlatformApi;
use App\Runovia\Resources\ProductApi;
use App\Runovia\Resources\ReportApi;
use App\Runovia\Resources\ResourceApi;
use App\Runovia\RunoviaClient;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Wires the Runovia API client into the container and the views.
 */
class RunoviaServiceProvider extends ServiceProvider
{
    /**
     * ! Every resource API takes the same two collaborators, so they are registered
     *   from one loop rather than nine near-identical closures. Adding a resource
     *   means adding one class name here.
     */
    private const RESOURCE_APIS = [
        ActivityLogApi::class,
        AuthApi::class,
        BankAccountApi::class,
        BusinessApi::class,
        CustomerApi::class,
        InvoiceApi::class,
        PackageApi::class,
        PaymentApi::class,
        PlatformApi::class,
        ProductApi::class,
        ReportApi::class,
    ];

    public function register(): void
    {
        /*
         * ! Singleton. The APP token is cached inside the client, and a fresh
         *   instance per injection point would mean several token exchanges in one
         *   request the first time the cache is cold.
         */
        $this->app->singleton(RunoviaClient::class, fn ($app): RunoviaClient => new RunoviaClient(
            $app->make(HttpFactory::class),
            $app->make(CacheRepository::class),
        ));

        /*
         * ! Scoped, not singleton. It wraps the request's session, and a singleton
         *   would hold the first request's session for the life of the process under
         *   a persistent worker (Octane) — which is how one user ends up seeing
         *   another's identity.
         */
        $this->app->scoped(ApiSession::class, fn ($app): ApiSession => new ApiSession(
            $app->make('session.store'),
        ));

        foreach (self::RESOURCE_APIS as $class) {
            $this->app->scoped($class, fn ($app): ResourceApi => new $class(
                $app->make(RunoviaClient::class),
                $app->make(ApiSession::class),
            ));
        }
    }

    public function boot(): void
    {
        /*
         * ! `apiSession` is available to every view so the shell — the sidebar, the
         *   user menu, the button-visibility hints — does not need every controller
         *   to pass it. A view composer rather than a global: it is resolved from the
         *   container per request, so it carries this request's session.
         *
         * ! Views use it for DISPLAY decisions only. It must never be the reason an
         *   action is permitted; see ApiSession::canWrite().
         */
        View::composer('*', function ($view): void {
            $view->with('apiSession', $this->app->make(ApiSession::class));
        });
    }
}
