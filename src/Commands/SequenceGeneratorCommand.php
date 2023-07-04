<?php

namespace Onetech\ExportDocs\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Onetech\ExportDocs\Services\ParserService;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use File;
use ReflectionClass;

class SequenceGeneratorCommand extends Command
{
    protected $signature = 'docs:sequence';
    private ParserService $parser;

    public function handle()
    {
        $this->parser = new ParserService();
        $routePath = base_path('routes');
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($routePath));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                if (Str::contains($file->getFileName(), 'api')) {
                    if(!preg_match('/routes\/(.*?)\//', $file->getPathname())) {
                        continue;
                    }
                    $this->info($file->getPathname());
                    $contentRoute = File::get($file->getPathname());
                    $astRoute = $this->parser->parseAst($contentRoute);
                    $nodeRouteUriList = $this->parser->finder->find($astRoute, function (Node $node) {
                        if (
                            $node instanceof Expression
                            && $node->expr instanceof StaticCall
                            && isset($node->expr->args[0])
                            && $node->expr->args[0] instanceof Arg
                            && $node->expr->args[0]->value instanceof String_
                        ) {
                            return $node;
                        }
                    });

                    foreach ($nodeRouteUriList as $nodeRouteUri) {
                        $nodeRouteUriArr = $this->parser->finder->findFirst($nodeRouteUri, function (Node $node) {
                            if (
                                $node instanceof Node\Expr\Array_
                            ) {
                                return $node;
                            }
                        });
                        if(!$nodeRouteUriArr) {
                            continue;
                        }
                        $classController = '';
                        $classControllerAction = '';
                        if(property_exists($nodeRouteUriArr, 'items')) {
                            if(
                                property_exists($nodeRouteUriArr->items[0], 'value')
                                && property_exists($nodeRouteUriArr->items[0]->value, 'class')
                            ) {
                                $classController = $nodeRouteUriArr->items[0]->value->class->toString();
                            }
                            if(
                                property_exists($nodeRouteUriArr->items[1], 'value')
                                && property_exists($nodeRouteUriArr->items[1]->value, 'value')
                            ) {
                                $classControllerAction = $nodeRouteUriArr->items[1]->value->value;
                            }
                        }

                        $classControllerUse = $this->parser->findUseUse($astRoute, $classController);
                        if(!$classControllerUse) {
                            continue;
                        }

                        $reflectionClass = new ReflectionClass($classControllerUse->name->toString());
                        $controllerFilePath = $reflectionClass->getFileName();

                        if(!file_exists($controllerFilePath)) {
                            continue;
                        }

                        $contentController = File::get($controllerFilePath);
                        $controllerAst = $this->parser->parseAst($contentController);

                        $nodeControllerAction = $this->parser->findClassMethod($controllerAst, $classControllerAction);
                        if(!$nodeControllerAction) {
                            continue;
                        }
                        //Parse controller action from routes
                        dd($nodeControllerAction);

                    }
                }
            }
        }
    }

    public function analyticClass()
    {

    }
}


