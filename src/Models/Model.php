<?php

namespace Onetech\ExportDocs\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Model
{
    private $model;

    private $label;

    private EloquentModel $eloquentModel;

    private $relations;

    public function __construct(string $model, string $label, Collection $relations)
    {
        $this->model = $model;
        $this->eloquentModel = app($model);
        $this->label = $label;
        $this->relations = $relations;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return string
     */
    public function getNodeName()
    {
        return str_replace('-', '', Str::slug($this->model));
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return mixed
     */
    public function getTableName()
    {
        return $this->eloquentModel->getTable();
    }

    public function getEloquentModel(): EloquentModel
    {
        return $this->eloquentModel;
    }

    /**
     * @return Collection
     */
    public function getRelations()
    {
        return $this->relations;
    }
}
