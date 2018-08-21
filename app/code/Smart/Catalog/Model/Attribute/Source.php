<?php

namespace Smart\Catalog\Model\Attribute;

use Smart\Catalog\Model\ResourceModel\TestMultiselect\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory as OptionCollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Escaper;


class Source extends Table implements OptionSourceInterface
{
    private $collectionFactory;

    public function __construct(
        OptionCollectionFactory $attrOptionCollectionFactory,
        OptionFactory $attrOptionFactory,
        Escaper $escaper = null,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($attrOptionCollectionFactory, $attrOptionFactory, $escaper);

        $this->collectionFactory = $collectionFactory;
    }

    public function getAllOptions($withEmpty = true, $defaultValues = false)
    {
        if ($this->_options) {
            return $this->_options;
        }

        $this->_options = [];
        $collection = $this->collectionFactory->create();
        foreach ($collection as $item) {
            $this->_options[] = ['value' => $item->getId(), 'label' => $item->getData('option_text')];
        }

        return $this->_options;
    }
}