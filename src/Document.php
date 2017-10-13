<?php

namespace Mysticquent;

use Elasticsearch\Client;
use Illuminate\Support\Collection;
use Mysticquent\Exceptions\InvalidArgumentException;
use Mysticquent\Exceptions\MissingArgumentException;
use Mysticquent\Searchable;
use Mysticquent\Facades\Mysticquent;

class Document
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var mixed
     */
    protected $model;

    /**
     * PersistenceAbstract constructor.
     *
     */
    public function __construct()
    {
        $this->setClient(\Mysticquent::client());
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
     * Get the model to persist.
     *
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set the model to persist.
     *
     * @param Model $model
     *
     * @throws InvalidArgumentException
     *
     * @return $this
     */
    public function model($model)
    {
        $this->exitIfItemNotUseSearchable($model);

        $this->model = $model;

        return $this;
    }

    /**
     * Save a model instance.
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function save()
    {
        $this->exitIfModelNotSet();

        if (!$this->model->exists) {
            throw new \Exception('Model not persisted yet');
        }

        $params = [
            'id'    => $this->model->getKey(),
            'type'  => $this->model->getDocumentType(),
            'index' => $this->model->getDocumentIndex(),
            'body'  => $this->model->getDocumentData(),
        ];

        return $this->client->index($params);
    }

    /**
     * Update a model document.
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function update()
    {
        $this->exitIfModelNotSet();

        if (!$this->model->exists) {
            throw new \Exception('Model not persisted yet');
        }

        $params = [
            'id'    => $this->model->getKey(),
            'type'  => $this->model->getDocumentType(),
            'index' => $this->model->getDocumentIndex(),
            'body'  => [
                'doc' => $this->model->getDocumentData(),
            ],
        ];

        return $this->client->update($params);
    }

    /**
     * Delete a model document.
     *
     * @return mixed
     */
    public function delete()
    {
        $this->exitIfModelNotSet();

        $params = [
            'id'    => $this->model->getKey(),
            'type'  => $this->model->getDocumentType(),
            'index' => $this->model->getDocumentIndex(),
        ];

        // check if the document exists before deleting
        if ($this->client->exists($params)) {
            return $this->client->delete($params);
        }

        return true;
    }

    /**
     * Bulk save a collection Models.
     *
     * @param array|Collection $collection
     *
     * @return mixed
     */
    public function bulkSave($collection = [])
    {
        $params = [];
        foreach ($collection as $item) {
            $params['body'][] = [
                'index' => [
                    '_id'    => $item->getKey(),
                    '_type'  => $item->getDocumentType(),
                    '_index' => $item->getDocumentIndex(),
                ],
            ];
            $params['body'][] = $item->getDocumentData();
        }

        return $this->client->bulk($params);
    }

    /**
     * Bulk Delete a collection of Models.
     *
     * @param array|collection $collection
     *
     * @return mixed
     */
    public function bulkDelete($collection = [])
    {
        $params = [];
        foreach ($collection as $item) {
            $params['body'][] = [
                'delete' => [
                    '_id'    => $item->getKey(),
                    '_type'  => $item->getDocumentType(),
                    '_index' => $item->getDocumentIndex(),
                ],
            ];
        }

        return $this->client->bulk($params);
    }

    /**
     * Function called when the model value is a required.
     */
    private function exitIfModelNotSet()
    {
        if (!$this->model) {
            throw new MissingArgumentException('you should set the model first');
        }
    }

    /**
     * Check collection if use searchable.
     */
    private function exitIfItemCollectionNotUseSearchable($collection = [])
    {
        foreach ($collection as $item) {
            $this->exitIfItemNotUseSearchable($item);
        }
    }

    /**
     * Check item use searchable.
     */
    private function exitIfItemNotUseSearchable($item)
    {
        $traits = class_uses_recursive(get_class($item));

        if (!isset($traits[Searchable::class])) {
            throw new InvalidArgumentException(get_class($item).' does not use the searchable trait');
        }
    }

}
