<?php

namespace App\Providers;

use App\Interfaces\AuthenticationInterface;
use App\Interfaces\FolderInterface;
use App\Interfaces\UserInterface;
use App\Models\Folder;
use App\Observers\FolderObserver;
use App\Repository\AuthenticationRepository;
use App\Repository\FolderRepository;
use App\Repository\UserRepository;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthenticationInterface::class,AuthenticationRepository::class);
        $this->app->bind(UserInterface::class,UserRepository::class);
        $this->app->bind(FolderInterface::class,FolderRepository::class);
    }

    public function boot(): void
    {
        Folder::observe(FolderObserver::class);
    }
}
