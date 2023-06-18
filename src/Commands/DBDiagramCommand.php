<?php

namespace Onetech\ExportDocs\Commands;

use File;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Onetech\ExportDocs\Models\Model as GraphModel;
use Onetech\ExportDocs\Services\Diagrams\GraphBuilder;
use Onetech\ExportDocs\Services\Diagrams\RelationFinder;
use phpDocumentor\GraphViz\Graph;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use ReflectionClass;

class DBDiagramCommand extends Command
{
    const FORMAT_TEXT = 'text';

    const DEFAULT_FILENAME = 'graph';

    protected $signature = 'db:diagram {filename?} {--format=png}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate ER diagram.';

    /** @var RelationFinder */
    protected $relationFinder;

    /** @var Graph */
    protected $graph;

    /** @var GraphBuilder */
    protected $graphBuilder;

    public function __construct(RelationFinder $relationFinder, GraphBuilder $graphBuilder)
    {
        parent::__construct();

        $this->relationFinder = $relationFinder;
        $this->graphBuilder = $graphBuilder;
    }

    public function handle()
    {
        $tables = $this->getModelsThatShouldBeInspected();
        $models = collect([]);
        foreach ($tables as $table) {
            $tableName = class_basename($table);
            $tableName = Str::snake(Str::plural($tableName));
            // Get the column details from the information_schema table
            $columnDetails = DB::table('information_schema.columns')
                // ->select('column_name', 'data_type', 'numeric_precision', 'character_maximum_length', 'column_comment')
                ->select('column_name', 'column_type')
                ->where('table_schema', '=', DB::connection()->getDatabaseName())
                ->where('table_name', '=', $tableName)
                ->get();

            if (count($columnDetails) <= 0) {
                continue;
            }

            $models->push($table);
        }

        $this->info("Found {$models->count()} models.");
        $this->info('Inspecting model relations.');

        $bar = $this->output->createProgressBar($models->count());

        $models->transform(function ($model) use ($bar) {
            $bar->advance();

            return new GraphModel(
                $model,
                (new ReflectionClass($model))->getShortName(),
                $this->relationFinder->getModelRelations($model)
            );
        });

        $graph = $this->graphBuilder->buildGraph($models);

        if ($this->option('format') === self::FORMAT_TEXT) {
            $this->info($graph->__toString());

            return;
        }

        $graph->export($this->option('format'), $this->getOutputFileName());

        $this->info(PHP_EOL);
        $this->info('Wrote diagram to ' . $this->getOutputFileName());
    }

    protected function getModelsThatShouldBeInspected(): Collection
    {
        $directories = config('export-docs.path.generator.model_directories');

        $modelsFromDirectories = $this->getAllModelsFromEachDirectory($directories);

        return $modelsFromDirectories;
    }

    protected function getAllModelsFromEachDirectory(array $directories): Collection
    {
        return collect($directories)
            ->map(function ($directory) {
                return $this->getModelsInDirectory($directory)->all();
            })
            ->flatten();
    }

    public function getModelsInDirectory(string $directory): Collection
    {
        $files = File::allFiles($directory);

        $ignoreModels = array_filter(config('export-docs.ignore', []), 'is_string');
        $whitelistModels = array_filter(config('export-docs.whitelist', []), 'is_string');

        $collection = Collection::make($files)->filter(function ($path) {
            return Str::endsWith($path, '.php');
        })->map(function ($path) {
            return $this->getFullyQualifiedClassNameFromFile($path);
        })->filter(function (string $className) {
            return !empty($className)
                && is_subclass_of($className, EloquentModel::class)
                && !(new ReflectionClass($className))->isAbstract();
        });

        if (!count($whitelistModels)) {
            return $collection->diff($ignoreModels)->sort();
        }

        return $collection->filter(function (string $className) use ($whitelistModels) {
            return in_array($className, $whitelistModels);
        });
    }

    protected function getFullyQualifiedClassNameFromFile(string $path): string
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        $code = file_get_contents($path);

        $statements = $parser->parse($code);
        $statements = $traverser->traverse($statements);

        // get the first namespace declaration in the file
        $rootStatement = collect($statements)->first(function ($statement) {
            return $statement instanceof Namespace_;
        });

        if (!$rootStatement) {
            return '';
        }

        return collect($rootStatement->stmts)
            ->filter(function ($statement) {
                return $statement instanceof Class_;
            })
            ->map(function (Class_ $statement) {
                return $statement->namespacedName->toString();
            })
            ->first() ?? '';
    }

    protected function getOutputFileName(): string
    {
        return $this->argument('filename') ?:
            static::DEFAULT_FILENAME . '.' . $this->option('format');
    }
}
