<?php

namespace Smart\ImportExport\Model\Import\Attribute;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\ImportExport\Model\Import\Source\Csv as CsvSource;

/**
 * For importing option labels in a file
 *
 * @package Smart\ImportExport\Model\Import\Attribute
 */
class Dataset
{
    private $attribute;

    private $attributeRepository;

    public function __construct(
        ProductAttributeInterface $attribute,
        ProductAttributeRepositoryInterface $attributeRepository
    ) {
        $this->attribute = $attribute;
        $this->attributeRepository = $attributeRepository;
    }

    public function import(CsvSource $source, $valueColumn, array $stores)
    {
        $labels = [];
        $source->rewind();
        while ($source->valid()) {
            $current = $source->current();
            $labels[] = $current[$valueColumn];
            $source->next();
        }

        $this->setOptions($labels, $stores);
        $this->attributeRepository->save($this->attribute);
    }

    /**
     * @return \Magento\Catalog\Api\Data\ProductAttributeInterface
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @return \Magento\Catalog\Api\ProductAttributeRepositoryInterface
     */
    public function getAttributeRepository()
    {
        return $this->attributeRepository;
    }

    private function setOptions(array $labels, $stores)
    {
        $existingOptions = $this->attribute->getOptions() ?? [];
        foreach ($existingOptions as $option) {
            $existingLabel = $option->getData('label');
            $index = array_search($existingLabel, $labels, true);
            if ($index !== false) {
                unset($labels[$index]);

                continue;
            }
        }

        if (empty($labels)) {
            return;
        }

        $options = [];

        foreach ($labels as $label) {
            $option = [];
            $option['label'] = $label;
            $storeLabels = [];
            foreach ($stores as $store) {
                $storeLabels['store_id'] = $store->getId();
                $storeLabels['label'] = $label;
            }

            $option['store_labels'][] = $storeLabels;
            $options[] = $option;
        }

        $this->attribute->unsetData('options');
        $this->attribute->setData('options', $options);
    }
}