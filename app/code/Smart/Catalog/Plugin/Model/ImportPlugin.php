<?php

namespace Smart\Catalog\Plugin\Model;

use Smart\Catalog\Model\Attribute\Source;
use Smart\Catalog\Repository\ImportRepositoryInterface;
use Smart\ImportExport\Model\Import\Attribute\Dataset;
use Magento\ImportExport\Model\Import\Source\Csv as CsvSource;

class ImportPlugin
{
    const ATTRIBUTE_CODE = 'test_multiselect';

    private $repository;

    public function __construct(ImportRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function aroundImport(
        Dataset $subject,
        callable $proceed,
        CsvSource $source,
        $valueColumn,
        array $stores
    ) {
        $attribute = $subject->getAttribute();

        if ($attribute->getAttributeCode() !== self::ATTRIBUTE_CODE) {
            return $proceed($source, $valueColumn, $stores);
        }

        $this->repository->import($source);
        $attribute->setSourceModel(Source::class);
        $subject->getAttributeRepository()->save($attribute);

        return null;
    }
}