<?php

namespace Mysticquent\Builders;

use Elasticsearch\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Mysticquent\Exceptions\InvalidArgumentException;
use Mysticquent\Searchable;
use ONGR\ElasticsearchDSL\Search as Query;

class BaseBuilder
{
    use Macroable;

    /**
     * An instance of client for elastic search.
     *
     * @var Client
     */
    public $client;

    /**
     * An instance of DSL query.
     *
     * @var Query
     */
    public $query;

    /**
     * The config to build client.
     *
     * @var array
     */
    protected $config;

    /**
     * The elastic index to query against.
     *
     * @var string
     */
    public $index;

    /**
     * Builder constructor.
     *
     */
    public function __construct()
    {
        $this->setClient(\Mysticquent::client());
        $this->setQuery(new Query());
        $this->setConfig(config('mysticquent'));
        $this->setIndex($this->getConfig()['index']);
    }

    /**
     * Get the configuration.
     *
     * @return array
     */
    public function getConfig() : array
    {
        return $this->config;
    }

    /**
     * Set the configuration to client.
     *
     * @param array $index
     *
     * @return $this
     */
    public function setConfig(array $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get the elastic index to query against.
     *
     * @return array
     */
    public function getIndex() : array
    {
        return $this->index;
    }

    /**
     * Set the elastic index to query against.
     *
     * @param mixed $index
     *
     * @return $this
     */
    public function setIndex($index)
    {
        $this->index = array_flatten([$index]);

        return $this;
    }

    /**
     * Get the elastic search client instance.
     *
     * @return Client
     */
    public function getClient() : Client
    {
        return $this->client;
    }

    /**
     * Set a custom elastic client.
     *
     * @param Client $client
     *
     * @return $this
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get the elastic search query instance.
     *
     * @return Query
     */
    public function getQuery() : Query
    {
        return $this->query;
    }

    /**
     * Set a custom elastic query.
     *
     * @param Query $query
     *
     * @return $this
     */
    public function setQuery(Query $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Set the eloquent model to use when querying elastic search.
     *
     * @param mixed $model
     *
     * @throws InvalidArgumentException
     *
     * @return $this
     */
    public function setModel($model)
    {
        // Check if the model is searchable before setting the query builder model
        $traits = class_uses_recursive(get_class($model));

        if (! isset($traits[Searchable::class])) {
            throw new InvalidArgumentException(get_class($model).' does not use the searchable trait');
        }

        if ($index = $model->getDocumentIndex()) {
            $this->setIndex($index);
        }

        return $this;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    private function setStatementIndex(array $params) : array
    {
        if (isset($params['index']) and $params['index']) {
            return $params;
        }

        // merge the default index with the given params if the index is not set.
        return array_merge($params, ['index' => $this->getIndex()]);
    }
}
