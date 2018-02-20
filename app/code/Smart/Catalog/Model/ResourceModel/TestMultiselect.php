<?php

namespace Smart\Catalog\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class TestMultiselect extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(
            'smart_catalog_test_multiselect',
            'option_id'
        );
    }

    public function loadByOptionText($object, $optionText)
    {
        return $this->load($object, $optionText, 'option_text');
    }
}