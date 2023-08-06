<?php

namespace Rector\Tests\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictScalarReturnExprRector\Fixture;

class SkipIfChildClassHasTyped
{
    public function test()
    {
        return 0;
    }
}

class SomeChild extends SkipIfChildClassHasTyped
{
    public function test(): string
    {
        $a = '1';

        return $a;
    }

}