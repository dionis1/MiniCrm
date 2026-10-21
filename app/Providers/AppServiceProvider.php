<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        ResetPassword::createUrlUsing(fn ($user, string $token) => url('/reset-password/'.$token).'?'.http_build_query(['email' => $user->email]));
        Model::preventLazyLoading(! $this->app->isProduction());
        RateLimiter::for('crm-api', fn (Request $request) => Limit::perMinute(180)->by($request->user()?->id ?? $request->ip()));
    }
}
