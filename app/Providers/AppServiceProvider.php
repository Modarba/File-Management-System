<?php

namespace App\Providers;

use App\Interfaces\AuthenticationInterface;
use App\Interfaces\FolderInterface;
use App\Interfaces\UserInterface;
use App\Repository\AuthenticationRepository;
use App\Repository\FolderRepository;
use App\Repository\UserRepository;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuthenticationInterface::class,AuthenticationRepository::class);
        $this->app->bind(UserInterface::class,UserRepository::class);
        $this->app->bind(FolderInterface::class,FolderRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
