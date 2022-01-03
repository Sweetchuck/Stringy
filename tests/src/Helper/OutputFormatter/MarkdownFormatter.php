<?php

declare(strict_types = 1);

namespace Stringy\Tests\Helper\OutputFormatter;

use Consolidation\OutputFormatters\Formatters\FormatterInterface;
use Consolidation\OutputFormatters\Formatters\HumanReadableFormat;
use Consolidation\OutputFormatters\Options\FormatterOptions;
use Consolidation\OutputFormatters\Validate\ValidDataTypesInterface;
use Consolidation\OutputFormatters\Validate\ValidDataTypesTrait;
use Stringy\Tests\Helper\ClassMethod\ClassMethodConverter;
use Stringy\Tests\Helper\ClassMethod\ClassMethodsCommandResult;
use Stringy\Tests\Helper\ClassMethod\ClassMethodsResult;
use Symfony\Component\Console\Output\OutputInterface;

class MarkdownFormatter implements
    FormatterInterface,
    HumanReadableFormat,
    ValidDataTypesInterface
{

    use ValidDataTypesTrait;

    public function validDataTypes()
    {
        return [
            new \ReflectionClass(ClassMethodsCommandResult::class),
            new \ReflectionClass(ClassMethodsResult::class),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validate($structuredData)
    {
        return $structuredData;
    }

    /**
     * {@inheritdoc}
     */
    public function write(
        OutputInterface $output,
        $data,
        FormatterOptions $options
    ) {
        if ($data instanceof ClassMethodsResult) {
            $converter = new ClassMethodConverter();
            $output->write($converter->toMarkdownHeaders($data->methods));
        }
    }
}
