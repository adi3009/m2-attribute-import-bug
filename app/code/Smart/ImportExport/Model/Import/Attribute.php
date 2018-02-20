<?php

namespace Smart\ImportExport\Model\Import;

use Smart\ImportExport\Model\Import\Attribute\Dataset\SourceProvider;
use Smart\ImportExport\Model\Import\Attribute\DatasetFactory;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Model\Entity\Attribute\FrontendLabelFactory;
use Magento\Eav\Model\Entity\Attribute\GroupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\Event\Manager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\ImportExport\Model\Import\ErrorProcessing\{
    ProcessingError, ProcessingErrorAggregatorInterface
};
use Magento\ImportExport\Model\Import\AbstractSource;
use Magento\Store\Model\StoreManagerInterface;

class Attribute implements AttributeInterface
{
    private $attributeFactory;
    private $attributeRepository;
    private $errorAggregator;
    private $frontendLabelFactory;
    private $groupFactory;
    private $setFactory;
    private $storeManager;
    private $stores;
    private $dataSetFactory;
    private $defaultAttributeSet;
    private $importedAttributeGroup;

    /** @var  AbstractSource */
    private $source;

    /** @var  SourceProvider */
    private $datasetSourceProvider;
    private $eventManager;

    public function __construct(
        AttributeFactory $attributeFactory,
        ProductAttributeRepositoryInterface $attributeRepository,
        ProcessingErrorAggregatorInterface $errorAggregator,
        FrontendLabelFactory $frontendLabelFactory,
        GroupFactory $groupFactory,
        SetFactory $setFactory,
        StoreManagerInterface $storeManager,
        DatasetFactory $dataSetFactory,
        AbstractSource $source,
        SourceProvider $datasetSourceProvider,
        Manager $eventManager
    ) {
        $this->attributeFactory = $attributeFactory;
        $this->attributeRepository = $attributeRepository;
        $this->errorAggregator = $errorAggregator;
        $this->frontendLabelFactory = $frontendLabelFactory;
        $this->groupFactory = $groupFactory;
        $this->setFactory = $setFactory;
        $this->storeManager = $storeManager;
        $this->stores = [];
        $this->dataSetFactory = $dataSetFactory;
        $this->source = $source;
        $this->datasetSourceProvider = $datasetSourceProvider;
        $this->eventManager = $eventManager;
    }

    public function import(callable $onError) {
        $this->initStores();
        $this->source->rewind();
        $row = 1;
        while ($this->source->valid()) {
            $attributeData = $this->source->current();
            $attributeCode = $attributeData['attribute_code'];
            try {
                $attribute = $this->attributeRepository->get($attributeCode);
            } catch (NoSuchEntityException $e) {
                /** @var ProductAttributeInterface $attribute */
                $attribute = $this->attributeFactory->create();
            }

            $hasOptions = $attributeData['options'] ?? false;
            if ($hasOptions) {
                $this->setOptions($attribute, $attributeData);
            }

            unset($attributeData['options']);
            $attribute->addData($attributeData);
            $attribute->setFrontendInput($attributeData['frontend_input']);
            $labels = $this->createLabels($attributeData['label']);
            $attribute->unsetData('label');
            $attribute->setFrontendLabels($labels);

            try {
                $this->eventManager->dispatch(
                    'smart_import_attribute_before',
                    ['attribute' => $attribute]
                );
                $this->attributeRepository->save($attribute);
                $dataSetId = $attributeData['data_set_id'];
                if ($dataSetId) {
                    $this->importDataSet($attributeData, $attribute);
                }
                $this->assignToGroup($attribute);
            } catch (\Exception $e) {
                $this->errorAggregator->addError(
                    $e->getCode(),
                    ProcessingError::ERROR_LEVEL_WARNING,
                    $row,
                    null,
                    $e->getMessage()
                );
            }

            $this->source->next();
            $row++;
        }

        $errors = $this->errorAggregator->getAllErrors();
        if (!empty($errors)) {
            $onError($errors);

            return false;
        }

        $this->eventManager->dispatch('smart_import_attributes_complete');

        return true;
    }

    private function createLabels(string $labelsString)
    {
        $labels = explode(';', $labelsString);
        $storeLabels = [];
        foreach ($labels as $label) {
            $labelData = explode('=', $label);
            $storeLabel = $this->frontendLabelFactory->create();
            $storeId = $this->stores[$labelData[0]]->getStoreId();
            $storeLabel->setStoreId($storeId);
            $storeLabel->setLabel($labelData[1]);
            $storeLabels[] = $storeLabel;
        }

        return $storeLabels;
    }

    private function initStores()
    {
        if (!empty($this->stores)) {
            return $this->stores;
        }

        $this->stores = $this->storeManager->getStores(true, true);

        return $this->stores;
    }

    private function setOptions(ProductAttributeInterface $attribute, array $attributeData)
    {
        $existingOptions = $attribute->getOptions() ?? [];
        $nonEmptyOptions = [];
        $existingOptionLabels = [];
        foreach ($existingOptions as $option) {
            if (!$option->getValue()) {
                continue;
            }

            $existingOptionLabels[] = $option->getData('label');
            $nonEmptyOptions[] = $option;
        }

        if (!empty($nonEmptyOptions)) {
            // Removing empty option set by getOptions method.
            $attribute->setOptions($nonEmptyOptions);
        }

        $options = $this->formatAttributeOption($attributeData, $existingOptionLabels);
        if (empty($options)) {
            return;
        }

        $attribute->unsetData('options');
        $attribute->setData('options', $options);
    }

    private function formatAttributeOption($attributeData, $existingOptionLabels)
    {
        $inputType = $attributeData['frontend_input'] ?? 'not-set';
        if ($inputType !== 'select' && $inputType !== 'multiselect' && $inputType !== 'price') {
            return [];
        }

        /*
         * $optionsData is string in format store_code=option_label,default_store_code=option_label;
         * e.g. admin=option1label,default=option1label;admin=option2label,default=option2label
         */
        $optionsData = $attributeData['options'];
        $optionsData = explode(';', $optionsData);
        $options = [];
        foreach ($optionsData as $optionData) {
            $optionData = trim($optionData);
            if (!$optionData) {
                continue;
            }

            $storesLabels = explode(',', $optionData);
            $option = [];
            foreach ($storesLabels as $storeAndLabel) {
                $labelData = explode('=', $storeAndLabel);
                /** @var \Magento\Store\Model\Store $store */
                $store = $this->stores[$labelData[0]];
                if (in_array($labelData[1], $existingOptionLabels)) {
                    break;
                }

                if ($store->isDefault()) {
                    $option['label'] = $labelData[1];
                }

                $storeId = $store->getStoreId();
                $option['store_labels'][] = ['store_id' => $storeId, 'label' => $labelData[1]];
            }

            if (!empty($option)) {
                $options[] = $option;
            }
        }

        return $options;
    }

    private function importDataSet($attributeData, $attribute) {
        $dataSetId = $attributeData['data_set_id'];
        $valueColumn = $attributeData['data_set_value_column'];
        $source = $this->datasetSourceProvider->provideFor($attribute->getAttributeCode(), $dataSetId);
        $dataSet = $this->dataSetFactory->create(
            ['attribute' => $attribute, 'attributeRepository' => $this->attributeRepository]
        );

        $dataSet->import($source, $valueColumn, $this->stores);
    }

    private function assignToGroup($attribute)
    {
        $group = $this->getImportedAttributeGroup();
        $attribute->getResource()->saveInSetIncluding(
            $attribute,
            $attribute->getAttributeId(),
            $group->getAttributeSetId(),
            $group->getId()
        );
    }

    private function getImportedAttributeGroup()
    {
        if ($this->importedAttributeGroup) {
            return $this->importedAttributeGroup;
        }

        $group = $this->groupFactory->create();
        $group->getResource()->load($group, 'imported', 'attribute_group_code');

        if ($group->getId()) {
            $this->importedAttributeGroup = $group;

            return $this->importedAttributeGroup;
        }

        $group->setAttributeSetId($this->getDefaultSet()->getId())
            ->setAttributeGroupName('Imported')
            ->setAttributeGroupCode('imported');
        $group->getResource()->save($group);
        $this->importedAttributeGroup = $group;

        return $this->importedAttributeGroup;
    }

    private function getDefaultSet()
    {
        if ($this->defaultAttributeSet) {
            return $this->defaultAttributeSet;
        }

        $set = $this->setFactory->create();
        $setCollection = $set->getCollection()
            ->addFieldToFilter('attribute_set_name', 'Default')
            ->addFieldToFilter('entity_type_id', 4);

        $this->defaultAttributeSet = $setCollection->getFirstItem();

        return $this->defaultAttributeSet;
    }
}