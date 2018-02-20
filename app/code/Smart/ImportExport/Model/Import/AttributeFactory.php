<?php

namespace Smart\ImportExport\Model\Import;

use Smart\ImportExport\Model\Import\Attribute\Dataset\SourceProviderFactory as DatasetSourceProviderFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;
use Magento\ImportExport\Model\Import\Source\CsvFactory;
use Magento\Framework\FilesystemFactory;

class AttributeFactory
{
    private $csvFactory;
    private $datasetSourceProviderFactory;
    private $filesystemFactory;
    private $instanceName;
    private $objectManager;

    public function __construct(
        CsvFactory $csvFactory,
        DatasetSourceProviderFactory $datasetSourceProviderFactory,
        FilesystemFactory $filesystemFactory,
        ObjectManagerInterface $objectManager,
        $instanceName = Attribute::class
    ) {
        $this->filesystemFactory = $filesystemFactory;
        $this->csvFactory = $csvFactory;
        $this->datasetSourceProviderFactory = $datasetSourceProviderFactory;
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
    }

    public function create(array $data)
    {
        $file = $data['attributes_file'] ?? null;
        $datasetDirectory = $data['data_set_directory'] ?? null;
        if (!$file || !$datasetDirectory) {
            throw new \Exception(
                'Can not create object, missing required args attributes_file and data_set_directory'
            );
        }

        $readDirectory = $this->filesystemFactory->create()->getDirectoryRead(
            DirectoryList::VAR_DIR
        );
        $csvSource = $this->csvFactory->create(['file' => $file, 'directory' => $readDirectory]);
        $datasetSourceProvider = $this->datasetSourceProviderFactory->create(
            [
                'csvSourceFactory'  => $this->csvFactory,
                'fileSystemFactory' => $this->filesystemFactory,
                'path'              => $datasetDirectory,
            ]
        );

        /** @var Attribute $attributeImporter */
        $attributeImporter = $this->objectManager->create(
            $this->instanceName,
            ['source' => $csvSource, 'datasetSourceProvider' => $datasetSourceProvider]
        );

        return $attributeImporter;
    }
}