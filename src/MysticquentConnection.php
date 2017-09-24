<?php

namespace Bidzm\Mysticquent;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use ONGR\ElasticsearchDSL\Search as DSLQuery;
use Bidzm\Mysticquent\Builders\SearchBuilder;
use Bidzm\Mysticquent\Builders\SuggestionBuilder;
use Bidzm\Mysticquent\Map\Builder as MapBuilder;
use Bidzm\Mysticquent\Map\Grammar as MapGrammar;
use Bidzm\Mysticquent\Persistence\EloquentPersistence;

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
    protected $elastic;

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
        $this->elastic = $this->client();

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
     * Get map builder instance for this connection.
     *
     * @return MapBuilder
     */
    public function getMapBuilder()
    {
        return new MapBuilder($this);
    }

    /**
     * Get map grammar instance for this connection.
     *
     * @return MapBuilder
     */
    public function getMapGrammar()
    {
        return new MapGrammar();
    }

    /**
     * Get DSL grammar instance for this connection.
     *
     * @return DSLGrammar
     */
    public function getDSLQuery()
    {
        return new DSLQuery();
    }

    /**
     * Get the elastic search client instance.
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->elastic;
    }

    /**
     * Set a custom elastic client.
     *
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->elastic = $client;
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
     * Execute a map statement on index;.
     *
     * @param array $mappings
     *
     * @return array
     */
    public function mapStatement(array $mappings)
    {
        return $this->elastic->indices()->putMapping($this->setStatementIndex($mappings));
    }

    /**
     * Execute a map statement on index;.
     *
     * @param array $suggestions
     *
     * @return array
     */
    public function suggestStatement(array $suggestions)
    {
        return $this->elastic->suggest($this->setStatementIndex($suggestions));
    }

    /**
     * Begin a fluent search query builder.
     *
     * @return SearchBuilder
     */
    public function search()
    {
        return new SearchBuilder();
    }

    /**
     * Begin a fluent suggest query builder.
     *
     * @return SuggestionBuilder
     */
    public function suggest()
    {
        return new SuggestionBuilder($this, $this->getDSLQuery());
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
