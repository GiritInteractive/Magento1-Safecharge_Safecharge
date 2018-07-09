<?php

/* @var $installer Mage_Customer_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

// Create tables.
$tableName = $installer->getTable('safecharge_safecharge/request');
if ($installer->getConnection()->isTableExists($tableName) === false) {
    $table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn('log_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
            array(
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true,
            ), 'Log Id')
        ->addColumn('request_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50,
            array(),
            'Request Id')
        ->addColumn('parent_request_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50,
            array( // TODO: Possible that it will be not needed.
            ), 'Parent Request Id')
        ->addColumn('method', Varien_Db_Ddl_Table::TYPE_TEXT, 30,
            array(),
            'Method')
        ->addColumn('request', Varien_Db_Ddl_Table::TYPE_TEXT, null,
            array(),
            'Request')
        ->addColumn('response', Varien_Db_Ddl_Table::TYPE_TEXT, null,
            array(),
            'Response')
        ->addColumn('increment_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50,
            array(),
            'Increment Id')
        ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null,
            array(
                'unsigned' => true,
                'nullable' => true,
            ), 'Customer Id')
        ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null,
            array(
                'unsigned' => true,
                'default' => '0',
            ), 'Store Id')
        ->addColumn('status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null,
            array(
                'unsigned' => true,
            ), 'Store Id')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null,
            array(
                'nullable' => false,
            ), 'Created At')
        ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null,
            array(
                'nullable' => false,
            ), 'Updated At')
        ->addIndex(
            $installer->getIdxName(
                'safecharge_safecharge/request',
                array('request_id')
            ),
            array('request_id')
        )
        ->addIndex(
            $installer->getIdxName(
                'safecharge_safecharge/request',
                array('parent_request_id')
            ),
            array('parent_request_id')
        )
        ->addIndex(
            $installer->getIdxName(
                'safecharge_safecharge/request',
                array('store_id')
            ),
            array('store_id')
        )
        ->addIndex(
            $installer->getIdxName(
                'safecharge_safecharge/request',
                array('status')
            ),
            array('status')
        )
        ->setComment('Api Request Log');
    $installer->getConnection()->createTable($table);
}

$tableName = $installer->getTable('safecharge_safecharge/vault');
if ($installer->getConnection()->isTableExists($tableName) === false) {
    $table = $installer->getConnection()
        ->newTable($installer->getTable('safecharge_safecharge/vault'))
        ->addColumn('vault_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
            array(
                'identity' => true,
                'unsigned' => true,
                'nullable' => false,
                'primary' => true,
            ), 'Vault Id')
        ->addColumn('customer_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null,
            array(
                'unsigned' => true,
                'nullable' => true,
            ), 'Customer Id')
        ->addColumn('public_hash', Varien_Db_Ddl_Table::TYPE_VARCHAR, 128,
            array(
                'nullable' => false,
            ), 'Public Hash')
        ->addColumn('payment_method_code', Varien_Db_Ddl_Table::TYPE_VARCHAR,
            128,
            array(
                'nullable' => false,
            ), 'Payment Method Code')
        ->addColumn('type', Varien_Db_Ddl_Table::TYPE_VARCHAR, 128,
            array(
                'nullable' => false,
            ), 'Type')
        ->addColumn('gateway_token', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255,
            array(
                'nullable' => false,
            ), 'Gateway Token')
        ->addColumn('token_details', Varien_Db_Ddl_Table::TYPE_TEXT, null,
            array(
                'nullable' => true,
            ), 'Details')
        ->addColumn('is_active', Varien_Db_Ddl_Table::TYPE_TINYINT, 1,
            array(
                'unsigned' => true,
                'nullable' => false,
                'default' => 1,
            ), 'Is Active')
        ->addColumn('is_visible', Varien_Db_Ddl_Table::TYPE_TINYINT, 1,
            array(
                'unsigned' => true,
                'nullable' => false,
                'default' => 1,
            ), 'Is Visible')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null,
            array(
                'nullable' => false,
            ), 'Created At')
        ->addColumn('expires_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null,
            array(
                'nullable' => false,
            ), 'Expires At')
        ->addIndex(
            $installer->getIdxName(
                'safecharge_safecharge/vault',
                array('public_hash')
            ),
            array('public_hash')
        )
        ->addIndex(
            $installer->getIdxName(
                'safecharge_safecharge/vault',
                array('is_active')
            ),
            array('is_active')
        )
        ->addIndex(
            $installer->getIdxName(
                'safecharge_safecharge/vault',
                array('is_visible')
            ),
            array('is_visible')
        )
        ->addIndex(
            $installer->getIdxName(
                'safecharge_safecharge/vault',
                array('customer_id')
            ),
            array('customer_id')
        )
        ->setComment('Vault');
    $installer->getConnection()->createTable($table);
}

// Insert statuses.
$installer->getConnection()->insertArray(
    $installer->getTable('sales/order_status'),
    array(
        'status',
        'label'
    ),
    array(
        array('status' => 'sc_voided', 'label' => 'SC Voided'),
        array('status' => 'sc_settled', 'label' => 'SC Settled'),
        array('status' => 'sc_partially_settled', 'label' => 'SC Partially Settled'),
        array('status' => 'sc_auth', 'label' => 'SC Auth'),
    )
);

// Insert states and mapping of statuses to states.
$installer->getConnection()->insertArray(
    $installer->getTable('sales/order_status_state'),
    array(
        'status',
        'state',
        'is_default'
    ),
    array(
        array(
            'status' => 'sc_voided',
            'state' => 'processing',
            'is_default' => 0,
        ),
        array(
            'status' => 'sc_settled',
            'state' => 'processing',
            'is_default' => 0,
        ),
        array(
            'status' => 'sc_partially_settled',
            'state' => 'processing',
            'is_default' => 0,
        ),
        array(
            'status' => 'sc_auth',
            'state' => 'processing',
            'is_default' => 0,
        )
    )
);

$installer->endSetup();
