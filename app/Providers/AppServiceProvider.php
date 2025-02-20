<?php

namespace App\Providers;

use App\Interface1\authenticationInterface;
use App\Interface1\UserInterface;
use App\Repository\authenticationRepository;
use App\Repository\UserRepository;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(authenticationInterface::class,authenticationRepository::class);
        $this->app->bind(UserInterface::class,UserRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
