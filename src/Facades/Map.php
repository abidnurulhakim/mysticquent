<?php

namespace Bidzm\Elostic\Facades;

use Illuminate\Support\Facades\Facade;

class Map extends Facade
{
    /**
     * Get a map builder instance for the default connection.
     *
     * @return \Bidzm\Elostic\Map\Builder
     */
    protected static function getFacadeAccessor()
    {
        return static::$app['elostic']->connection()->getMapBuilder();
    }
}
