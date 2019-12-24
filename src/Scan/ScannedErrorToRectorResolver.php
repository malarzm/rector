<?php

declare(strict_types=1);

namespace Rector\Scan;

use Nette\Utils\Strings;
use Rector\Exception\NotImplementedException;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;
use Rector\ValueObject\Scan\Argument;
use Rector\ValueObject\Scan\ClassMethodWithArguments;

final class ScannedErrorToRectorResolver
{
    /**
     * @see https://regex101.com/r/RbJy9h/1/
     * @var string
     */
    private const INCOMPATIBLE_PARAM_TYPE_PATTERN = '#Declaration of (?<current>\w.*?) should be compatible with (?<should_be>\w.*?)$#';

    /**
     * @see https://regex101.com/r/RbJy9h/2/
     * @var string
     */
    private const CLASS_METHOD_ARGUMENTS_PATTERN = '#(?<class>.*?)::(?<method>.*?)\((?<arguments>.*?)\)#';

    /**
     * @see https://regex101.com/r/RbJy9h/5
     * @var string
     */
    private const ARGUMENTS_PATTERN = '#(\b(?<type>\w.*?)?\b )?\$(?<name>\w+)#sm';

    /**
     * @var mixed[]
     */
    private $paramChanges = [];

    /**
     * @param string[] $errors
     * @return mixed[]
     */
    public function processErrors(array $errors): array
    {
        $this->paramChanges = [];

        foreach ($errors as $fatalError) {
            $match = Strings::match($fatalError, self::INCOMPATIBLE_PARAM_TYPE_PATTERN);
            if (! $match) {
                continue;
            }

            if (! Strings::contains($match['current'], '::')) {
                // probably a function?
                throw new NotImplementedException();
            }

            $scannedMethod = $this->createScannedMethod($match['current']);
            $shouldBeMethod = $this->createScannedMethod($match['should_be']);

            $this->collectClassMethodDifferences($scannedMethod, $shouldBeMethod);
        }

        if ($this->paramChanges === []) {
            return [];
        }

        return [
            AddParamTypeDeclarationRector::class => $this->paramChanges,
        ];
    }

    private function createScannedMethod(string $classMethodWithArgumentsDescription): ClassMethodWithArguments
    {
        $match = Strings::match($classMethodWithArgumentsDescription, self::CLASS_METHOD_ARGUMENTS_PATTERN);

        if (! $match) {
            throw new NotImplementedException();
        }

        $arguments = $this->createArguments((string) $match['arguments']);

        return new ClassMethodWithArguments($match['class'], $match['method'], $arguments);
    }

    /**
     * @return Argument[]
     */
    private function createArguments(string $argumentsDescription): array
    {
        $arguments = [];

        $argumentDescriptions = Strings::split($argumentsDescription, '#\b,\b#');
        foreach ($argumentDescriptions as $position => $argumentDescription) {
            $match = Strings::match((string) $argumentDescription, self::ARGUMENTS_PATTERN);
            if (! $match) {
                throw new NotImplementedException();
            }

            $arguments[] = new Argument($match['name'], $position, $match['type'] ?? '');
        }

        return $arguments;
    }

    private function collectClassMethodDifferences(
        ClassMethodWithArguments $scannedMethod,
        ClassMethodWithArguments $shouldBeMethod
    ): void {
        foreach ($scannedMethod->getArguments() as $scannedMethodArgument) {
            $shouldBeArgument = $shouldBeMethod->getArgumentByPosition($scannedMethodArgument->getPosition());

            if ($shouldBeArgument === null) {
                throw new NotImplementedException();
            }

            // types are identical, nothing to change
            if ($scannedMethodArgument->getType() === $shouldBeArgument->getType()) {
                continue;
            }

            $this->paramChanges[$scannedMethod->getClass()][$scannedMethod->getMethod()][$scannedMethodArgument->getPosition()] = $shouldBeArgument->getType();
        }
    }
}
