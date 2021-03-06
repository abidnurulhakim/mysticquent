<?php

namespace Mysticquent;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mysticquent\Document;
use Mysticquent\Facades\Mysticquent;
use Mysticquent\Map\Blueprint;

trait Searchable
{
    /**
     * Hit score after querying Elasticsearch.
     *
     * @var null|int
     */
    public $documentScore = null;

    /**
     * Searchable boot model.
     */
    public static function bootSearchable()
    {
        static::saved(function ($model) {
            if ($model->shouldSyncDocument()) {
                $model->document()->save();
            }
        });

        static::deleted(function ($model) {
            if ($model->shouldSyncDocument()) {
                $model->document()->delete();
            }
        });
    }

    /**
     * Start an elastic persistence query builder.
     *
     * @return \Mysticquet\Document
     */
    private function document()
    {
        return Mysticquent::document()->model($this);
    }

    /**
     * Reindex of model.
     *
     */
    public function reindex()
    {
        $this->document()->save();
    }

    /**
     * Get the model elastic type.
     *
     * @return string
     */
    public function getDocumentType()
    {
        // if the type is defined use it else return the table name
        if (isset($this->documentType) and !empty($this->documentType)) {
            return $this->documentType;
        }

        return get_class($this);
    }

    /**
     * Get the model elastic index if available.
     *
     * @return mixed
     */
    public function getDocumentIndex()
    {
        // if a custom index is defined use it else return null
        if (isset($this->documentIndex) and !empty($this->documentIndex)) {
            return $this->documentIndex;
        }
        $index = $this->getTable().'_'.env('APP_ENV');
        return preg_replace('/\_$/', '', $index);
    }

    /**
     * Build the document data.
     *
     * @return array
     */
    public function buildDocument() : array
    {
        $document = [];
        foreach (array_keys($this->getAttributes()) as $attribute) {
            $document[$attribute] = $this->$attribute;
        }
        return $document;
    }

    /**
     * Get the document data with the appropriate method.
     *
     * @return array
     */
    public function getDocumentData() : array
    {
        $dataRaw = $this->buildDocument();
        $document = [];
        foreach ($dataRaw as $attribute => $value) {
            if ($value instanceof Collection) {
                $value = $value->toArray();
            } elseif ($value instanceof Carbon) {
                $value = $value->format('Y-m-d\TH:i:s\Z');
            }
            $document[$attribute] = $value;
        }
        // append suggest
        $input = [];
        foreach ($this->getSuggesterAttributes() as $attribute) {
            $value = $this->$attribute ?? array_get($dataRaw, $attribute);
            if ($value instanceof Collection) {
                $value = $value->toArray();
            }
            if (is_string($value) && preg_match('/[A-z]+/', $value)) {
                $input[] = $value;
            } elseif (is_array($value) && !is_associative_array($value)) {
                foreach ($value as $val) {
                    $input[] = $val;
                }
            }
        }
        $document['_suggest'] = $input;

        return $document;
    }

    /**
     * Get attributes for suggester.
     *
     * @return array
     */
    public function getSuggesterAttributes() : array
    {
        return $this->suggester ?? array_keys($this->buildDocument());
    }

    /**
     * Mapping.
     *
     */
    protected function mapping()
    {
        return function(Blueprint $map) {
            $map->completion('_suggest', ['analyzer' => 'simple', 'search_analyzer' => 'simple']);
        };
    }

    /**
     * Checks if the model content should be auto synced with elastic.
     *
     * @return boolean;
     */
    public function shouldSyncDocument()
    {
        if (property_exists($this, 'syncDocument')) {
            return $this->syncDocument;
        }

        return true;
    }

    /**
     * Reindex bulk Models.
     *
     */
    public static function reindexAll()
    {
        $model = new static;
        self::chunk(1000, function ($models) use ($model) {
            $model->document()->bulkSave($models);
        });
    }

    /**
     * Create index of Models.
     *
     */
    public static function createIndex()
    {
        $model = new static;
        self::deleteIndex();
        Mysticquent::client()->indices()
            ->create($model->defaultMapping());

        self::runMapping();
    }

    /**
     * Delete index of Models.
     *
     */
    public static function deleteIndex()
    {
        $model = new static;
        $indexParams = [
            'index' => $model->getDocumentIndex()
        ];
        $exists = Mysticquent::client()->indices()->exists($indexParams);
        if ($exists) {
            Mysticquent::client()->indices()->delete($indexParams);
        }
    }

    /**
     * Reset index of Models.
     *
     */
    public static function resetIndex(bool $reindex = true)
    {
        self::createIndex();
        if ($reindex) {
            self::reindexAll();
        }
    }

    /**
     * Run Mapping.
     *
     */
    public static function runMapping()
    {
        $model = new static;
        Mysticquent::map()->create($model->getDocumentType(), $model->mapping(), $model->getDocumentIndex());
    }

    /**
     * Start an elastic search query builder.
     *
     * @return \Mysticquet\Builder\SearchBuilder
     */
    public static function search($keyword = '*', array $attributes = [])
    {
        $model = new static;
        return Mysticquent::search($keyword, $attributes)->setModel($model);
    }

    /**
     * Start an elastic suggestion query builder.
     *
     * @return \Mysticquet\Builder\SuggestionBuilder
     */
    public static function suggest()
    {
        $model = new static;
        return Mysticquent::suggest()->setModel($model);
    }

    /**
     * Get default mapping
     *
     * @return array
     */
    private function defaultMapping()
    {
        $version = config('mysticquent.elasticsearch_version', '5.3.5');
        if (preg_match('/^5.*/', $version)) {
            return self::defaultMappingV5();
        } else {
            return self::defaultMappingV2();
        }
    }

    /**
     * Get default mapping
     *
     * @return array
     */
    private function defaultMappingV2()
    {
        return [
                'index' => $this->getDocumentIndex(),
                'body' => [
                    'mappings' => [
                        '_default_' => [
                             'dynamic_templates' => [
                                 [
                                     'strings' => [
                                         'match' => '*',
                                         'match_mapping_type' => 'string',
                                         'mapping' => [
                                             'type' => 'string',
                                             'fields' => [
                                                 '{name}' => [
                                                     'include_in_all' => true,
                                                     'index' => 'not_analyzed',
                                                     'type' => 'string'
                                                 ],
                                                 'analyzed' => [
                                                     'index' => 'analyzed',
                                                     'type' => 'string'
                                                 ]
                                             ]
                                         ],
                                     ]
                                 ]
                             ]
                        ]
                    ]
                ]
            ];
    }

    /**
     * Get default mapping
     *
     * @return array
     */
    private function defaultMappingV5()
    {
        return [
                'index' => $this->getDocumentIndex(),
                'body' => [
                    'mappings' => [
                        '_default_' => [
                             'dynamic_templates' => [
                                 [
                                     'strings' => [
                                         'match' => '*',
                                         'match_mapping_type' => 'string',
                                         'mapping' => [
                                             'type' => 'keyword',
                                             'fields' => [
                                                 '{name}' => [
                                                     'include_in_all' => true,
                                                     'index' => 'not_analyzed',
                                                     'type' => 'string'
                                                 ],
                                                 'analyzed' => [
                                                     'index' => 'analyzed',
                                                     'type' => 'string'
                                                 ]
                                             ]
                                         ],
                                     ]
                                 ]
                             ]
                        ]
                    ]
                ]
            ];
    }
}
