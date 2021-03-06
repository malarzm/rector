<?php

declare(strict_types=1);

namespace Rector\Symfony\Tests\Rector\HttpKernel\GetRequestRector;

use Iterator;
use Rector\Symfony\Rector\HttpKernel\GetRequestRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class GetRequestRectorTest extends AbstractRectorTestCase
{
    /**
     * @dataProvider provideDataForTest()
     */
    public function test(string $file): void
    {
        $this->doTestFile($file);
    }

    public function provideDataForTest(): Iterator
    {
        return $this->yieldFilesFromDirectory(__DIR__ . '/Fixture');
    }

    protected function getRectorClass(): string
    {
        return GetRequestRector::class;
    }
}
