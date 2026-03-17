<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\TierAwardServiceInterface;
use App\Services\TierAwardService;

class TierAwardServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            TierAwardServiceInterface::class,
            TierAwardService::class
        );
    }

}
