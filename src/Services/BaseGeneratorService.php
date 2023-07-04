<?php

namespace Onetech\ExportDocs\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class BaseGeneratorService
{
    public function cleanup(): void
    {
        Artisan::call('optimize:clear');
    }

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getContentFile($fullPath): string
    {
        return File::get($fullPath);
    }

    public function fileExists($fullPath): bool
    {
        return File::exists($fullPath);
    }

    public function deleteFile($fullPath): bool
    {
        if ($this->fileExists($fullPath)) {
            return File::delete($fullPath);
        }

        return false;
    }

    public function createFile($fullPath, $content): bool|int
    {
        $dirPath = File::dirname($fullPath);
        if (!file_exists($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        return File::put($fullPath, $content);
    }

    public function modififyBasePathGenerator(Collection $generators, $isRemoveBasePath = false): Collection
    {
        $basePath = base_path('/');
        foreach ($generators as $key => $value) {

            if (!\Str::contains($key, '_path') && !\Str::contains($key, 'stub')) {
                continue;
            }

            if ($isRemoveBasePath && \Str::contains($value, $basePath)) {
                $generators[$key] = str_replace($basePath, '', $value);
            }

            if (!$isRemoveBasePath && !\Str::contains($value, $basePath)) {
                $generators[$key] = $basePath . $value;
            }
        }

        return $generators;
    }

    /**
     * Relationships
     *
     * @return array of relationships
     */
    public function getRelationships(Model $model): array
    {
        $relationships = [];
        $model = new $model();

        foreach ((new ReflectionClass($model))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (
                $method->class != get_class($model) ||
                !empty($method->getParameters()) ||
                $method->getName() == __FUNCTION__
            ) {
                continue;
            }

            try {
                $return = $method->invoke($model);
                // check if not instance of Relation
                if (!($return instanceof Relation)) {
                    continue;
                }
                $relationType = (new ReflectionClass($return))->getShortName();
                $modelName = (new ReflectionClass($return->getRelated()))->getName();

                $foreignKey = $return->getQualifiedForeignKeyName();
                $parentKey = $return->getQualifiedParentKeyName();
                $relationships[$method->getName()] = [
                    'type' => $relationType,
                    'model' => class_basename($modelName),
                    'foreign_key' => $foreignKey,
                    'parent_key' => $parentKey,
                ];
            } catch (QueryException|\TypeError|\Throwable $e) {
                // ignore
            }
        }

        return $relationships;
    }
}
