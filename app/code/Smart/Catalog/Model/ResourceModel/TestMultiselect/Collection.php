<?php

namespace Smart\Catalog\Model\ResourceModel\TestMultiselect;

use Smart\Catalog\Model\TestMultiselect;
use Smart\Catalog\Model\ResourceModel\TestMultiselect as ResourceModelTestMultiselect;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(TestMultiselect::class, ResourceModelTestMultiselect::class);
    }
}