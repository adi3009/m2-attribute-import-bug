<?php

namespace Smart\Catalog\Repository;

use Magento\ImportExport\Model\Import\AbstractSource;

interface ImportRepositoryInterface
{
    /**
     * @param \Magento\ImportExport\Model\Import\AbstractSource $source
     *
     * @return mixed
     */
    public function import(AbstractSource $source);
}