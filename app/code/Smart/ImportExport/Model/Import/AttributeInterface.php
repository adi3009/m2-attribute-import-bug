<?php

namespace Smart\ImportExport\Model\Import;

interface AttributeInterface
{
    public function import(callable $onError);
}