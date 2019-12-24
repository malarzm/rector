<?php

declare(strict_types=1);

namespace Rector\Tests\Scan;

use Rector\HttpKernel\RectorKernel;
use Rector\Scan\ScannedErrorToRectorResolver;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;
use Symplify\PackageBuilder\Tests\AbstractKernelTestCase;

final class ScannedErrorToRectorResolverTest extends AbstractKernelTestCase
{
    /**
     * @var ScannedErrorToRectorResolver
     */
    private $scannedErrorToRectorResolver;

    protected function setUp(): void
    {
        $this->bootKernel(RectorKernel::class);

        $this->scannedErrorToRectorResolver = self::$container->get(ScannedErrorToRectorResolver::class);
    }

    public function test(): void
    {
        $errors = [];
        $errors[] = 'Declaration of Kedlubna\extendTest::add($message) should be compatible with Kedlubna\test::add(string $message = \'\')';

        $rectorConfiguration = $this->scannedErrorToRectorResolver->processErrors($errors);

        $expectedConfiguration = [
            AddParamTypeDeclarationRector::class => [
                'Kedlubna\extendTest' => [
                    'add' => [
                        0 => 'string',
                    ],
                ],
            ],
        ];

        $this->assertSame($expectedConfiguration, $rectorConfiguration);
    }
}
