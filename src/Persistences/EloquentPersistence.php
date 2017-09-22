<?php

namespace Bidzm\Elostic\Persistences;

use Bidzm\Elostic\Persistences\ModelPersistence;
use Illuminate\Database\Eloquent\Model;

class EloquentPersistence extends ModelPersistence
{
    /**
     * Set the model to persist.
     *
     * @param Model $model
     *
     * @throws InvalidArgumentException
     *
     * @return $this
     */
    public function model(Model $model)
    {
        parent::model($model);
    }
}
