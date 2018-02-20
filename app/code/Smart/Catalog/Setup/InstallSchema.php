<?php

namespace Smart\Catalog\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $tableName = $setup->getTable('smart_catalog_test_multiselect');
        $table = $setup->getConnection()->newTable($tableName);

        $table->addColumn(
            'option_id',
            Table::TYPE_INTEGER,
            null,
            ['primary' => true, 'identity' => true, 'unsigned' => true, 'nullable' => false,],
            'Select ID'
        )->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
            'Creation Time'
        )->addColumn(
            'updated_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
            'Update Time'
        )->addColumn(
            'option_text',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Select Option'
        )->addIndex(
            'select_option_unique_index',
            ['option_text'],
            ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
        )->setComment('Selections');


        $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }
}