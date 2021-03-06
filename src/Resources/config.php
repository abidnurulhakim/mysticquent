<?php
use Elasticsearch\Client;

return [

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch version
    |--------------------------------------------------------------------------
    |
    | The version of your elasicsearch
    |
    */
    'elasticsearch_version' => env('MYSTICQUENT_ELASTICSEARCH_VERSION', '5.3.5'),

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    |
    | The default elastic index used with all eloquent model
    |
    */
    'index' => env('MYSTICQUENT_INDEX', '_all'),

    /*
     * Connection settings
     */
    'connection'     => [

        /*
        |--------------------------------------------------------------------------
        | Hosts
        |--------------------------------------------------------------------------
        |
        | The most common configuration is telling the client about your cluster: how many nodes, their addresses and ports.
        | If no hosts are specified, the client will attempt to connect to localhost:9200.
        |
        */
        'hosts'   => [
            env('MYSTICQUENT_HOST', '127.0.0.1:9200'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Reties
        |--------------------------------------------------------------------------
        |
        | By default, the client will retry n times, where n = number of nodes in your cluster.
        | A retry is only performed if the operation results in a "hard" exception.
        |
        */
        'retries' => env('MYSTICQUENT_RETRIES', 3),

        /*
        |------------------------------------------------------------------
        | Logging
        |------------------------------------------------------------------
        |
        | Logging is disabled by default for performance reasons. The recommended logger is Monolog (used by Laravel),
        | but any logger that implements the PSR/Log interface will work.
        |
        | @more https://www.elastic.co/guide/en/elasticsearch/client/php-api/2.0/_configuration.html#enabling_logger
        |
        */
        'logging' => [
            'enabled' => env('MYSTICQUENT_LOG', false),
            'path'    => storage_path(env('MYSTICQUENT_LOG_PATH', 'logs/mysticquent.log')),
            'level'   => env('MYSTICQUENT_LOG_LEVEL', 200),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapping type document
    |--------------------------------------------------------------------------
    |
    | Polymorphic attribute _type in document elastic use for load model from result search
    |
    */
    'mappings'   => [],

];
