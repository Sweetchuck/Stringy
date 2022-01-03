<?php

declare(strict_types = 1);

namespace Stringy\Tests\Helper\ClassMethod;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Lexer\Lexer as PhpDocLexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser as PhpDocConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator as PhpDocTokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser as PhpDocTypeParser;
use Stringy\Stringy;

class ClassMethodConverter
{

    /**
     * @param \ReflectionMethod[] $methods
     */
    public function toMarkdownHeaders(iterable $methods): string
    {
        $return = [];
        foreach ($methods as $method) {
            $return[] = $this->toMarkdownHeader($method);
        }

        return implode("\n\n", $return);
    }

    public function toMarkdownHeader(\ReflectionMethod $method): string
    {
        $lexer = new PhpDocLexer();
        $constExprParser = new PhpDocConstExprParser();
        $phpDocParser = new PhpDocParser(new PhpDocTypeParser($constExprParser), $constExprParser);

        $tokens = new PhpDocTokenIterator($lexer->tokenize($method->getDocComment()));
        $phpDocNode = $phpDocParser->parse($tokens);

        $return = '### method ' . $method->getName() . "\n";

        $i = 0;
        while (isset($phpDocNode->children[$i])
            && $phpDocNode->children[$i] instanceof PhpDocTextNode
        ) {
            $return .= "\n" . $phpDocNode->children[$i];
            $i++;
        }

        $return .= "\n```php\n" . $method->getName() . "(";
        $parameters = $this->markdownParameters($method->getParameters());
        if ($parameters) {
            $return .= "\n    " . implode(",\n    ", $parameters) . ",\n";
        }
        $return .= ")\n```\n";

        $rootDir = $this->getSelfRoot();
        $examples = Stringy::create(file_get_contents("$rootDir/tests/src/Helper/Examples.php"));

        $example = $examples->between(
            "\n// region " . $method->getName() . "\n",
            "\n// endregion",
        );
        if ($example->length()) {
            $return .= "\n**Examples:**";
            $return .= "\n```php\n$example\n```\n";
        }

        return $return;
    }

    /**
     * @param \ReflectionParameter[] $parameters
     *
     * @return string[]
     */
    protected function markdownParameters(array $parameters): array
    {
        $return = [];
        foreach ($parameters as $parameter) {
            $return[] = ($parameter->hasType() ? $parameter->getType() . ' ' : '')
                . '$' . $parameter->getName()
                . ($parameter->isOptional() ? ' = ' . var_export($parameter->getDefaultValue(), true) : '');
        }

        return $return;
    }

    protected function getSelfRoot(): string
    {
        return dirname(__DIR__, 4);
    }
}
