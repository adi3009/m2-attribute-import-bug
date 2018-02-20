<?php

namespace Smart\ImportExport\Console\Command\Product;

use Smart\ImportExport\Model\Import\AttributeFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\State as AppState;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{
    InputArgument, InputInterface
};
use Symfony\Component\Console\Output\OutputInterface;

class ImportAttributesCommand extends Command
{
    private $attributeImporterFactory;
    private $appState;
    private $output;

    public function __construct(
        AppState $appState,
        AttributeFactory $attributeImporterFactory
    ) {
        $this->appState = $appState;
        $this->attributeImporterFactory = $attributeImporterFactory;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('smart:import:product-attributes');
        $this->setDescription('Import product attributes');
        $this->addArgument(
            'file', InputArgument::REQUIRED, 'File to import from, path relative to var directory'
        );
        $this->addArgument(
            'dataset_directory', InputArgument::REQUIRED,
            'Data set directory, attribute options'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument('file');
        $sourcesDirectory = $input->getArgument('dataset_directory');
        $attributeImporter = $this->attributeImporterFactory->create(
            ['attributes_file' => $file, 'data_set_directory' => $sourcesDirectory]
        );

        $this->output = $output;
        $output->writeln('<info>Importing...</info>');
        $this->appState->setAreaCode(Area::AREA_CRONTAB);
        $attributeImporter->import(
            function ($errors) {
                $this->outputErrors($errors);
            }
        );
        $output->writeln('<info>Complete.</info>');
    }

    protected function outputErrors(array $errors)
    {
        $this->output->writeln('<error>Errors during import</error>');
        /** @var ProcessingError $processingError */
        foreach ($errors as $processingError) {
            $this->output->writeln($this->formatError($processingError));
        }
    }

    protected function formatError(ProcessingError $processingError)
    {
        return "<error>"
            . "ROW - {$processingError->getRowNumber()}"
            . " | CODE - {$processingError->getErrorCode()}"
            . " | MESSAGE - {$processingError->getErrorMessage()}"
            . "</error>";
    }
}