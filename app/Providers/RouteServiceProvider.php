<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        // Existing limiter - kept as is
        RateLimiter::for('manualdispatch', function ($request) {
            return Limit::perMinute(12)
                ->by(optional($request->user())->id ?: $request->ip());
        });

        // User API - 60 req/min when authenticated, 20 for guests
        RateLimiter::for('user-api', function ($request) {
            return $request->user()
                ? Limit::perMinute(60)->by('user:' . $request->user()->id)
                : Limit::perMinute(20)->by('guest:' . $request->ip());
        });

        // Driver API - 120 req/min when authenticated (drivers poll more), 10 for guests
        RateLimiter::for('driver-api', function ($request) {
            return $request->user()
                ? Limit::perMinute(120)->by('driver:' . $request->user()->id)
                : Limit::perMinute(10)->by('guest:' . $request->ip());
        });

        // Sensitive endpoints (login, OTP, register) - tight limit per IP
        RateLimiter::for('sensitive', function ($request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Too many attempts. Please try again later.',
                    ], 429);
                });
        });

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }
}