<?php

namespace Bidzm\Mysticquent;

use Bidzm\Mysticquent\Document;
use Bidzm\Mysticquent\Facades\Mysticquent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
     * @return \Bidzm\Mysticquet\Document
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
        return $this->getTable().'_'.env('APP_ENV');
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
            if (is_string($value) && preg_match('/[A-z]+/', $value)) {
                $input[] = $value;
            }
        }
        $document['_suggest']['input'] = $input;

        return $document;
    }

    public function getSuggesterAttributes() : array
    {
        return $this->suggester ?? array_keys($this->buildDocument());
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
     * Reset index of Models.
     *
     */
    public static function resetIndex(bool $reindex = true)
    {
        $model = new static;
        try {
            Mysticquent::client()->indices()->delete([
                'index' => $model->getDocumentIndex()
            ]);
        } catch (Exception $e) {}
        if ($reindex) {
            self::reindexAll();
        }
    }

    /**
     * Start an elastic search query builder.
     *
     * @return \Bidzm\Mysticquet\Builder\SearchBuilder
     */
    public static function search($keyword = '*', array $attributes = [])
    {
        $model = new static;
        return Mysticquent::search($keyword, $attributes)->setModel($model);
    }

    /**
     * Start an elastic suggestion query builder.
     *
     * @return \Bidzm\Mysticquet\Builder\SuggestionBuilder
     */
    public static function suggest()
    {
        $model = new static;
        return Mysticquent::suggest()->setModel($model);
    }
}
