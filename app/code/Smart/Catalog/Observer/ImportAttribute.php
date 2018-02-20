<?php

namespace Smart\Catalog\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ImportAttribute implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Api\Data\ProductAttributeInterface $attribute */
        $attribute = $observer->getData('attribute');
        if ($attribute->getAttributeCode() === 'test_multiselect') {
            $attribute->setIsVisibleOnFront(true)
                ->setBackendModel('Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend')
                ->setFrontendInput('multiselect')
                ->setUsedInProductListing(true);
        }
    }
}