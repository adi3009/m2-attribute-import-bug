<?php

namespace Smart\Catalog\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;

class TestMultiselect extends AbstractModel implements IdentityInterface
{
    const CACHE_TAG = 'smart_catalog_test_multiselect';

    protected function _construct()
    {
        $this->_init(ResourceModel\TestMultiselect::class);
    }

    /**
     * Return unique ID(s) for each object in system
     *
     * @return string[]
     */
    public function getIdentities()
    {
        if ($this->getId()) {
            return [self::CACHE_TAG . '_' . $this->getId()];
        }

        return [];
    }
}