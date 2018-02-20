<?php

namespace Smart\Catalog\Model\Attribute;

use Smart\Catalog\Model\ResourceModel\TestMultiselect\CollectionFactory;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Data\OptionSourceInterface;

class Source extends AbstractSource implements OptionSourceInterface
{
    private $collectionFactory;

    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function getAllOptions()
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