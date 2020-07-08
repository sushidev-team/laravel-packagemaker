<?php

namespace AMBERSIVE\PackageMaker;

use App;
use Str;

use Illuminate\Foundation\AliasLoader;

use Illuminate\Support\ServiceProvider;

class PackageMakerServiceProvider extends ServiceProvider
{

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
       
        // Configs
        $this->publishes([
            __DIR__.'/Configs/package-maker.php'         => config_path('package-maker.php'),
        ],'package-maker');

        // Commands

        if ($this->app->runningInConsole()) {

            $this->commands([
                \AMBERSIVE\PackageMaker\Console\Commands\Dev\MakePackage::class,
            ]);

        }

    }


}
