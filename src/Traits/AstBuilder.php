<?php

declare(strict_types=1);

namespace Onetech\ExportDocs\Traits;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Nop;

trait AstBuilder
{
    public function buildGetCollection($var, $key, $default = null)
    {
        return new MethodCall(
            new Variable($var),
            'get',
            [
                new String_($key),
                $this->checkBuildType($default),
            ]
        );
    }

    public function checkBuildType($value) {
        if (is_string($value)) {
            return new String_($value);
        }
        if (is_int($value)) {
            return new LNumber($value);
        }
        if (is_array($value)) {
            return new Arg(new ConstFetch(new Name('[]')));
        }

        return new Arg(new ConstFetch(new Name('null')));
    }

    public function newLine() {
        return new Nop();
    }
}
