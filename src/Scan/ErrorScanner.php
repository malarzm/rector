<?php

declare(strict_types=1);

namespace Rector\Scan;

use Rector\FileSystem\FilesFinder;

final class ErrorScanner
{
    /**
     * @var string[]
     */
    private $errors = [];

    /**
     * @var FilesFinder
     */
    private $filesFinder;

    public function __construct(FilesFinder $filesFinder)
    {
        $this->filesFinder = $filesFinder;
    }

    /**
     * @param string[] $source
     * @return string[]
     */
    public function scanSource(array $source): array
    {
        $this->setErrorHandler();

        $fileInfos = $this->filesFinder->findInDirectoriesAndFiles($source, ['php']);
        foreach ($fileInfos as $fileInfo) {
            // trigger fatal error if not compatible with parent
            include_once $fileInfo->getRealPath();
        }

        $this->restoreErrorHandler();

        return $this->errors;
    }

    /**
     * @see https://www.php.net/manual/en/function.set-error-handler.php
     */
    private function setErrorHandler(): void
    {
        set_error_handler(function (int $num, string $error): void {
            $this->errors[] = $error;
        });
    }

    private function restoreErrorHandler(): void
    {
        restore_error_handler();
    }
}
