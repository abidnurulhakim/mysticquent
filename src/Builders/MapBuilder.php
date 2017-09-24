<?php

namespace Bidzm\Mysticquent\Builders;

use Bidzm\Mysticquent\Builders\BaseBuilder;
use Bidzm\Mysticquent\Map\Blueprint;
use Bidzm\Mysticquent\Map\Grammar;
use Closure;

class MapBuilder extends BaseBuilder
{
    /**
     * Map grammar instance.
     *
     * @var Grammar
     */
    protected $grammar;

    /**
     * Blueprint resolver callback.
     *
     * @var Closure
     */
    protected $resolver;

    public function __construct()
    {
        parent::__construct();
        $this->grammar = new Grammar;
    }

    /**
     * Create a map on your elasticsearch index.
     *
     * @param string  $type
     * @param string  $index
     * @param Closure $callback
     */
    public function create($type, Closure $callback, $index = null)
    {
        if (!is_string($type)) {
            throw new InvalidArgumentException('type should be a string');
        }

        if ($index and !is_string($index)) {
            throw new InvalidArgumentException('index should be a string');
        }

        $blueprint = $this->createBlueprint($type, $closure = null, $index);

        $blueprint->create();

        $callback($blueprint);

        $this->build($blueprint);
    }

    /**
     * Execute the blueprint to build.
     *
     * @param Blueprint $blueprint
     */
    protected function build(Blueprint $blueprint)
    {
        $blueprint->build($this->grammar);
    }

    /**
     * Create a new command set with a Closure.
     *
     * @param string       $type
     * @param Closure|null $callback
     * @param null         $index
     *
     * @return mixed|Blueprint
     */
    protected function createBlueprint($type, Closure $callback = null, $index = null)
    {
        if (isset($this->resolver)) {
            return call_user_func($this->resolver, $type, $callback, $index);
        }

        return new Blueprint($type, $callback, $index);
    }

    /**
     * Set the Schema Blueprint resolver callback.
     *
     * @param \Closure $resolver
     *
     * @return void
     */
    public function blueprintResolver(Closure $resolver)
    {
        $this->resolver = $resolver;
    }
}
