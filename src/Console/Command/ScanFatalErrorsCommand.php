<?php

declare(strict_types=1);

namespace Rector\Console\Command;

use Rector\Configuration\Option;
use Rector\Console\Shell;
use Rector\Scan\ErrorScanner;
use Rector\Scan\ScannedErrorToRectorResolver;
use Rector\Yaml\YamlPrinter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\PackageBuilder\Console\Command\CommandNaming;

final class ScanFatalErrorsCommand extends AbstractCommand
{
    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var ScannedErrorToRectorResolver
     */
    private $scannedErrorToRectorResolver;

    /**
     * @var ErrorScanner
     */
    private $errorScanner;

    /**
     * @var YamlPrinter
     */
    private $yamlPrinter;

    public function __construct(
        SymfonyStyle $symfonyStyle,
        ScannedErrorToRectorResolver $scannedErrorToRectorResolver,
        ErrorScanner $errorScanner,
        YamlPrinter $yamlPrinter
    ) {
        $this->symfonyStyle = $symfonyStyle;
        $this->scannedErrorToRectorResolver = $scannedErrorToRectorResolver;
        $this->errorScanner = $errorScanner;
        $this->yamlPrinter = $yamlPrinter;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));

        $this->setDescription('Scan for fatal errors');
        $this->addArgument(
            Option::SOURCE,
            InputArgument::REQUIRED | InputArgument::IS_ARRAY,
            'Path to file/directory to process'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = $input->getArgument(Option::SOURCE);
        $errors = $this->errorScanner->scanSource($source);

        $rectorConfiguration = $this->scannedErrorToRectorResolver->processErrors($errors);

        if ($rectorConfiguration !== []) {
            $yamlContent = $this->yamlPrinter->printYamlToString($rectorConfiguration);

            $this->symfonyStyle->note('Add this configuration to your "rector.yaml" to complete types');
            $this->symfonyStyle->writeln($yamlContent);
        } else {
            $this->symfonyStyle->success('No fatal errors found');
        }

        return Shell::CODE_SUCCESS;
    }
}
