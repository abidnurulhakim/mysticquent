<?php

namespace Mysticquent;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Mysticquent\Builders\MapBuilder;
use Mysticquent\Builders\SearchBuilder;
use Mysticquent\Builders\SuggestionBuilder;

class MysticquentConnection
{
    /**
     * Elastic Search default index.
     *
     * @var string
     */
    protected $index;

    /**
     * Elasticsearch client instance.
     *
     * @var Client
     */
    protected $client;

    /**
     * Connection constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->client = $this->client();

        $this->setDefaultIndex($config['index']);
    }

    /**
     * Get the default elastic index.
     *
     * @return string
     */
    public function getDefaultIndex()
    {
        return $this->index;
    }

    /**
     * Set the default index.
     *
     * @param $index
     *
     * @return Connection
     */
    public function setDefaultIndex($index)
    {
        $this->index = $index;
    }

    /**
     * Get the elastic search client instance.
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set a custom elastic client.
     *
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Begin a fluent search query builder.
     *
     * @return SearchBuilder
     */
    public function search($keyword = '*', array $attributes = [])
    {
        return new SearchBuilder($keyword, $attributes);
    }

    /**
     * Begin a fluent suggest query builder.
     *
     * @return SuggestionBuilder
     */
    public function suggest()
    {
        return new SuggestionBuilder();
    }

    /**
     * Begin a fluent map query builder.
     *
     * @return MapBuilder
     */
    public function map()
    {
        return new MapBuilder();
    }

    /**
     * Create a new elastic persistence handler.
     *
     * @return Document
     */
    public function document()
    {
        return new Document($this);
    }

    /**
     * Create an elastic search instance.
     *
     * @return Client
     */
    public function client()
    {
        if (!$this->client) {
            $config = config('mysticquent.connection');
            $client = ClientBuilder::create()
                ->setHosts($config['hosts']);

            if (isset($config['retries'])) {
                $client->setRetries($config['retries']);
            }

            if (isset($config['logging']) and $config['logging']['enabled'] == true) {
                $logger = ClientBuilder::defaultLogger($config['logging']['path'], $config['logging']['level']);
                $client->setLogger($logger);
            }
            $this->client = $client->build();
        }
        return $this->client;
    }
}
