<?php

namespace Onetech\ExportDocs\Services\Diagrams;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Onetech\ExportDocs\Models\Model;
use Onetech\ExportDocs\Models\ModelRelation;
use phpDocumentor\GraphViz\Graph;
use phpDocumentor\GraphViz\Node;

class GraphBuilder
{
    /** @var Graph */
    private $graph;

    /**
     * @param $models
     * @return Graph
     */
    public function buildGraph(Collection $models): Graph
    {
        $this->graph = new Graph();

        foreach (config('export-docs.graph') as $key => $value) {
            $this->graph->{"set${key}"}($value);
        }

        $this->addModelsToGraph($models);

        return $this->graph;
    }

    protected function getTableColumnsFromModel(EloquentModel $model)
    {
        try {
            $table = $model->getConnection()->getTablePrefix() . $model->getTable();
            $schema = $model->getConnection()->getDoctrineSchemaManager($table);
            $databasePlatform = $schema->getDatabasePlatform();
            $databasePlatform->registerDoctrineTypeMapping('enum', 'string');

            $database = null;

            if (strpos($table, '.')) {
                [$database, $table] = explode('.', $table);
            }

            return $schema->listTableColumns($table, $database);
        } catch (\Throwable $e) {
        }

        return [];
    }

    protected function getModelLabel(EloquentModel $model, string $label)
    {

        $table = '<<table width="100%" height="100%" border="0" margin="0" cellborder="1" cellspacing="0" cellpadding="10">' . PHP_EOL;
        $table .= '<tr width="100%"><td width="100%" bgcolor="'.config('export-docs.table.header_background_color').'"><font color="'.config('export-docs.table.header_font_color').'">' . $label . '</font></td></tr>' . PHP_EOL;

        if (config('export-docs.use_db_schema')) {
            $columns = $this->getTableColumnsFromModel($model);
            //modified column names
            $columnModified = [];
            foreach ($columns as $column) {
                if(count($columnModified) > 7) {
                    continue;
                }
                // if(Str::contains($column->getName(), 'id')){
                // }
                $columnModified[] = $column;
            }

            $columns = $columnModified;

            foreach ($columns as $column) {
                $columnName = str_replace("\x08","",$column->getName());
                $label = $columnName;
                if (config('export-docs.use_column_types')) {
                    $label .= ' ('.$column->getType()->getName().')';
                }
                $table .= '<tr width="100%"><td port="' . $columnName . '" align="left" width="100%"  bgcolor="'.config('export-docs.table.row_background_color').'"><font color="'.config('export-docs.table.row_font_color').'" >' . $label . '</font></td></tr>' . PHP_EOL;
            }
        }

        $table .= '</table>>';

        return $table;
    }

    protected function addModelsToGraph(Collection $models)
    {
        // Add models to graph
        $models->map(function (Model $model) {
            $eloquentModel = $model->getEloquentModel();
            $this->addNodeToGraph($eloquentModel, $model->getNodeName(), $model->getTableName());
        });

        // Create relations
        $models->map(function ($model) {
            $this->addRelationToGraph($model);
        });
    }

    protected function addNodeToGraph(EloquentModel $eloquentModel, string $nodeName, string $label)
    {
        $node = Node::create($nodeName);
        $node->setLabel($this->getModelLabel($eloquentModel, $label));

        foreach (config('export-docs.node') as $key => $value) {
            $node->{"set${key}"}($value);
        }

        $this->graph->setNode($node);
    }

    protected function addRelationToGraph(Model $model)
    {
        $modelNode = $this->graph->findNode($model->getNodeName());

        /** @var ModelRelation $relation */
        foreach ($model->getRelations() as $relation) {
            if ($relation->getType() != 'BelongsTo') {
                continue;
            }
            if(in_array($relation->getName(), ['createdBy', 'updatedBy'])) {
                continue;
            }
            $relatedModelNode = $this->graph->findNode($relation->getModelNodeName());

            if ($relatedModelNode !== null) {
                $this->connectByRelation($model, $relation, $modelNode, $relatedModelNode);
            }
        }
    }

    /**
     * @param  Node  $modelNode
     * @param  Node  $relatedModelNode
     * @param  ModelRelation  $relation
     */
    protected function connectNodes(Node $modelNode, Node $relatedModelNode, ModelRelation $relation): void
    {
        $edge = Edge::create($modelNode, $relatedModelNode);
        $edge->setFromPort($relation->getLocalKey());
        $edge->setToPort($relation->getForeignKey());
        $edge->setLabel(' ');
        // $edge->setXLabel($relation->getType() . PHP_EOL . $relation->getName());

        foreach (config('export-docs.edge') as $key => $value) {
            $edge->{"set${key}"}($value);
        }

        foreach (config('export-docs.relations.' . $relation->getType(), []) as $key => $value) {
            $edge->{"set${key}"}($value);
        }

        $this->graph->link($edge);
    }

    /**
     * @param  Model  $model
     * @param  ModelRelation  $relation
     * @param  Node  $modelNode
     * @param  Node  $relatedModelNode
     * @return void
     */
    protected function connectBelongsToMany(
        Model $model,
        ModelRelation $relation,
        Node $modelNode,
        Node $relatedModelNode
    ): void {
        $relationName = $relation->getName();
        $eloquentModel = app($model->getModel());

        /** @var BelongsToMany $eloquentRelation */
        $eloquentRelation = $eloquentModel->$relationName();

        if (!$eloquentRelation instanceof BelongsToMany) {
            return;
        }

        $pivotClass = $eloquentRelation->getPivotClass();

        try {
            /** @var EloquentModel $relationModel */
            $pivotModel = app($pivotClass);
            $pivotModel->setTable($eloquentRelation->getTable());
            $label = (new \ReflectionClass($pivotClass))->getShortName();
            $pivotTable = $eloquentRelation->getTable();
            $this->addNodeToGraph($pivotModel, $pivotTable, $label);

            $pivotModelNode = $this->graph->findNode($pivotTable);

            $relation = new ModelRelation(
                $relationName,
                'BelongsToMany',
                $model->getModel(),
                $eloquentRelation->getParent()->getKeyName(),
                $eloquentRelation->getForeignPivotKeyName()
            );

            $this->connectNodes($modelNode, $pivotModelNode, $relation);

            $relation = new ModelRelation(
                $relationName,
                'BelongsToMany',
                $model->getModel(),
                $eloquentRelation->getRelatedPivotKeyName(),
                $eloquentRelation->getRelated()->getKeyName()
            );

            $this->connectNodes($pivotModelNode, $relatedModelNode, $relation);
        } catch (\ReflectionException $e){}
    }

    /**
     * @param  Model  $model
     * @param  ModelRelation  $relation
     * @param  Node  $modelNode
     * @param  Node  $relatedModelNode
     */
    protected function connectByRelation(
        Model $model,
        ModelRelation $relation,
        Node $modelNode,
        Node $relatedModelNode
    ): void {

        if ($relation->getType() === 'BelongsToMany') {
            $this->connectBelongsToMany($model, $relation, $modelNode, $relatedModelNode);

            return;
        }

        $this->connectNodes($modelNode, $relatedModelNode, $relation);
    }
}
