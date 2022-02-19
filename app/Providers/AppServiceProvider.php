<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Domain\WhoseName\IdentityQueryRepository;
use Infrastructure\WhoseName\YamlFileRepository;

class AppServiceProvider extends ServiceProvider
{

    public $bindings = [
        IdentityQueryRepository::class => YamlFileRepository::class,
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
