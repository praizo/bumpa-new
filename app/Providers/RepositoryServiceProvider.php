<?php

namespace App\Providers;

use App\Repositories\Contracts\AchievementRepositoryInterface;
use App\Repositories\Contracts\BadgeRepositoryInterface;
use App\Repositories\Eloquent\AchievementRepository;
use App\Repositories\Eloquent\BadgeRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AchievementRepositoryInterface::class,
            AchievementRepository::class
        );

        $this->app->bind(
            BadgeRepositoryInterface::class,
            BadgeRepository::class
        );
    }

    public function boot(): void
    {
        //
    }
}
