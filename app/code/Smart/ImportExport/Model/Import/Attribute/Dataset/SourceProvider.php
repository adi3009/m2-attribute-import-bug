<?php

namespace Smart\ImportExport\Model\Import\Attribute\Dataset;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\FilesystemFactory;
use Magento\ImportExport\Model\Import\Source\CsvFactory;

class SourceProvider
{
    private $csvSourceFactory;
    private $fileSystemFactory;
    private $path;
    private $sources;

    public function __construct(
        CsvFactory $csvSourceFactory,
        FilesystemFactory $fileSystemFactory,
        $path
    ) {
        $this->csvSourceFactory = $csvSourceFactory;
        $this->fileSystemFactory = $fileSystemFactory;
        $this->path = $path;
        $this->sources = [];
    }

    /**
     * @param $attributeCode
     * @param $dataSetId
     *
     * @return \Magento\ImportExport\Model\Import\Source\Csv
     */
    public function provideFor($attributeCode, $dataSetId)
    {
        $defined = $this->sources[$attributeCode] ?? null;
        if ($defined) {
            return $this->sources[$attributeCode];
        }

        $fileName = "{$attributeCode}_{$dataSetId}.csv";
        $file = $this->path . DIRECTORY_SEPARATOR . $fileName;
        $readDirectory = $this->fileSystemFactory->create()->getDirectoryRead(DirectoryList::VAR_DIR);
        $source = $this->csvSourceFactory->create(['file' => $file, 'directory' => $readDirectory]);
        $this->sources[$attributeCode] = $source;

        return $this->sources[$attributeCode];
    }
}