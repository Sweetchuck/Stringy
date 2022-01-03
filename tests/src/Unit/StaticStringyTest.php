<?php

declare(strict_types = 1);

namespace Stringy\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stringy\StaticStringy as S;
use Stringy\Stringy;

class StaticStringyTest extends TestCase
{

    public function testBadMethodCall()
    {
        $this->expectException(\BadMethodCallException::class);
        S::invalidMethod('foo');
    }

    public function testEmptyArgsInvocation()
    {
        $this->assertSame('', S::toLowerCase());
    }

    public function testInvocation()
    {
        $this->assertSame('foobar', S::toLowerCase('FOOBAR'));
    }

    public function testPartialArgsInvocation()
    {
        $this->assertSame(
            'foo',
            S::slice('foobar', 0, 3),
        );
    }

    public function testFullArgsInvocation()
    {
        $this->assertSame(
            'fòô',
            S::slice('fòôbàř', 0, 3, 'UTF-8'),
        );
    }

    public function testArrayReturnValue()
    {
        $this->assertSame(['a', 'b'], S::lines("a\nb"));
    }

    /**
     * Use reflection to ensure that all argument numbers are correct. Each
     * static method should accept 2 more arguments than their Stringy
     * equivalent.
     */
    public function testArgumentNumbers()
    {
        $staticStringyClass = new \ReflectionClass(S::class);
        $stringyClass = new \ReflectionClass(Stringy::class);

        S::count('a');
        $staticPropMethodArgs = $staticStringyClass->getStaticPropertyValue('methodArgs');
        foreach ($staticPropMethodArgs as $methodName => $actualNumOfArgs) {
            $method = $stringyClass->getMethod($methodName);

            $this->assertTrue($method->isPublic());
            $this->assertSame(
                $method->getNumberOfParameters() + 2,
                $actualNumOfArgs,
                "Invalid number of arguments for $methodName",
            );
        }
    }
}
