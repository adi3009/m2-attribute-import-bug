<?php

namespace Smart\Catalog\Repository;

use Magento\ImportExport\Model\Import\AbstractSource;
use Smart\Catalog\Model\TestMultiselectFactory;
use Smart\Catalog\Model\ResourceModel\TestMultiselectFactory as TestMultiselectResourceFactory;

class ImportRepository implements ImportRepositoryInterface
{
    private $modelFactory;

    private $resourceModelFactory;

    private $resourceModel;

    public function __construct(
        TestMultiselectFactory $modelFactory,
        TestMultiselectResourceFactory $resourceModelFactory
    ) {
        $this->modelFactory = $modelFactory;
        $this->resourceModelFactory = $resourceModelFactory;
    }


    public function import(AbstractSource $source)
    {
        $source->rewind();
        while ($source->valid()) {
            $data = $source->current();
            $this->saveModel($data);
            $source->next();
        }
    }

    private function saveModel($data)
    {
        $model = $this->modelFactory->create();
        $optionText = trim($data['option_text']);
        $this->getResourceModel()->loadByOptionText($model, $optionText);
        if ($model->getId()) {
            return;
        }

        $model->setData('option_text', $optionText);
        $this->getResourceModel()->save($model);
    }

    private function getResourceModel()
    {
        if ($this->resourceModel) {
            return $this->resourceModel;
        }

        $this->resourceModel = $this->resourceModelFactory->create();

        return $this->resourceModel;
    }
}