<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Domain\WhoseName\IdentityQueryRepository;
use Domain\WhoseName\PoolQueryRepository;
use Infrastructure\WhoseName\YamlFileRepository;
use Infrastructure\WhoseName\PoolYamlFileRepository;

class AppServiceProvider extends ServiceProvider
{

    public $bindings = [
        IdentityQueryRepository::class => YamlFileRepository::class,
        PoolQueryRepository::class => PoolYamlFileRepository::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
