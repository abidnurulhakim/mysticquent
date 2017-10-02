<?php

namespace Mysticquent;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Mysticquent\Facades\Mysticquent;

/**
 * @codeCoverageIgnore
 */
class MysticquentServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish the configuration path
        $this->publishes([
            __DIR__.'/Resources/config.php' => config_path('mysticquent.php'),
        ]);

        // Create the mapping folder
        $this->publishes([
            __DIR__.'/Resources/database' => database_path(),
        ], 'database');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerManager();
        $this->registerAlias();
    }

    /**
     *  Register plastic's Manager and connection.
     */
    protected function registerManager()
    {
        $this->app->singleton('mysticquent', function ($app) {
            return new MysticquentConnection($app['config']['mysticquent']);
        });
    }

    /**
     *  Register the Plastic alias.
     */
    protected function registerAlias()
    {
        AliasLoader::getInstance()->alias('Mysticquent', Mysticquent::class);
    }
}
