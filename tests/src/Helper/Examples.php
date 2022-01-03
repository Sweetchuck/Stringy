<?php

/**
 * @file
 * ${PHPDocFile}.
 */

use Stringy\Stringy as S;
use Stringy\StaticStringy as SS;
use PHPUnit\Framework\Assert;

require_once __DIR__ . '/../../../vendor/autoload.php';

// region append
Assert::assertEquals(
    'fòôbàř',
    S::create('fòô')->append('bàř'),
);
// endregion

// region between
Assert::assertEquals(
    'foo',
    S::create('{foo} and {bar}')->between('{', '}'),
);
// endregion

// region camelize
Assert::assertEquals(
    'camelCase',
    S::create('Camel-Case')->camelize(),
);
// endregion

// region chars
Assert::assertSame(
    ['f', 'ò', 'ô', 'b', 'à', 'ř'],
    S::create('fòôbàř')->chars(),
);
// endregion

// region contains
Assert::assertTrue(
    S::create('Ο συγγραφέας είπε')->contains('συγγραφέας'),
);
// endregion

// region containsAny
Assert::assertTrue(
    S::create('str contains foo')->containsAny(['foo', 'bar']),
);
// endregion

// region countSubstr
Assert::assertSame(
    2,
    S::create('Ο συγγραφέας είπε')->countSubstr('α'),
);
// endregion

// region dasherize
Assert::assertEquals(
    'foo-bar',
    S::create('fooBar')->dasherize(),
);
// endregion

// region endsWith
Assert::assertTrue(
    S::create('fòôbàř')->endsWith('bàř'),
);
// endregion













// region upperCamelize
Assert::assertEquals(
    'UpperCamelCase',
    S::create('Upper Camel-Case')->upperCamelize(),
);
// endregion

// region upperCaseFirst
Assert::assertEquals(
    'Σ foo',
    S::create('σ foo')->upperCaseFirst(),
);

Assert::assertSame(
    'Σ foo',
    SS::upperCaseFirst('σ foo'),
);
// endregion
