<?php

namespace Onetech\ExportDocs\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Onetech\Pattern\Traits\AstBuilder;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeDumper;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

class ParserService
{
    use AstBuilder;

    public NodeFinder $finder;

    public BuilderFactory $builder;

    public function __construct()
    {
        $this->finder = new NodeFinder();
        $this->builder = new BuilderFactory();
    }

    public function parseAst($content)
    {
        //Change class name
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse($content);

        return $ast;
    }

    public function getColumnsInDB(string $tableName): array
    {
        return Schema::getColumnListing($tableName);
    }

    public function hasTable(string $tableName): bool
    {
        return Schema::hasTable($tableName);
    }

    public function getForeignColumn($name): string
    {
        return Str::snake($name) . '_id';
    }

    public function findClass($ast, $keyword = ''): Node | null
    {
        return $this->finder->findFirst($ast, function (Node $node) use ($keyword) {
            if ($node instanceof Class_) {
                if ($keyword) {
                    return Str::contains($node->name->toString(), $keyword);
                } else {
                    return $node;
                }
            }
        });
    }

    public function findUseUse($ast, $keyword = ''): Node | null
    {
        return $this->finder->findFirst($ast, function (Node $node) use ($keyword) {
            if ($node instanceof UseUse) {
                if ($keyword) {
                    if(($node->alias && $node->alias->toString() == $keyword) || Str::contains($node->name->toString(), $keyword)) {
                        return $node;
                    }
                } else {
                    return $node;
                }
            }
        });
    }

    public function findClassMethod($ast, $keyword = ''): Node | null
    {
        return $this->finder->findFirst($ast, function (Node $node) use ($keyword) {
            if ($node instanceof ClassMethod) {
                if ($keyword) {
                    return Str::contains($node->name->toString(), $keyword);
                } else {
                    return $node;
                }
            }
        });
    }

    public function findVariable($ast, $keyword = ''): Node | null
    {
        return $this->finder->findFirst($ast, function (Node $node) use ($keyword) {
            if ($node instanceof Node\Expr\Variable) {
                if ($keyword) {
                    return $node->name == $keyword;
                } else {
                    return $node;
                }
            }
        });
    }

    public function findMethodCall($ast, $keyword = '', $operator = 'IN'): Node | null
    {
        return $this->finder->findFirst($ast, function (Node $node) use ($keyword, $operator) {
            if ($node instanceof MethodCall) {
                if ($keyword) {
                    if($operator === 'IN') {
                        if(Str::contains($node->name->toString(), $keyword)) {
                            return $node;
                        }
                    } else {
                        if($node->name->toString() === $keyword) {
                            return $node;
                        }
                    }
                } else {
                    return $node;
                }
            }
        });
    }

    public function prettyPrintFile($ast)
    {
        $printer = new Standard([
            'shortArraySyntax' => true,
            'shortListSyntax' => true,
            'useBracketedArraySyntax' => true,
        ]);

        $newCode = $printer->prettyPrintFile($ast);

        return $newCode;
    }

    public function dump($stmts)
    {
        $nodeDumper = new NodeDumper;

        return $nodeDumper->dump($stmts);
    }

    public function runArtisan($command): string
    {
        Artisan::call($command, []);
        $output = Artisan::output();
        otLogInfo('RUN ARTISAN::::');
        otLogInfo($output);
        return $output;
    }
}
