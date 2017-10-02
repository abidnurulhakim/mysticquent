<?php

namespace Mysticquent\Facades;

use Illuminate\Support\Facades\Facade;

class Mysticquent extends Facade
{
    /**
     * Get a plastic manager instance for the default connection.
     *
     * @return \Elostic\Builders\Builder
     */
    protected static function getFacadeAccessor()
    {
        return static::$app['mysticquent'];
    }
}
