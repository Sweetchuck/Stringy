<?php

declare(strict_types = 1);

namespace Stringy\Tests\Unit;

use PHPUnit\Framework\TestCase;

use Stringy\Stringy;
use function Stringy\create as s;

/**
 * @covers \Stringy\create
 */
class Create extends TestCase
{
    public function testCreate()
    {
        $stringy = s('foo bar', 'UTF-8');
        $this->assertInstanceOf(Stringy::class, $stringy);
        $this->assertSame('foo bar', (string) $stringy);
        $this->assertSame('UTF-8', $stringy->getEncoding());
    }
}
