<?php

/**
 * Magestore
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category    Magestore
 * @package     Magestore_Inventorywarehouse
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */
$installer = $this;
$installer->startSetup();

try {
    /** Edited by Magnus - 24082016 - Fix bug warehouse_id was not alter */
    $resource = Mage::getSingleton('core/resource');
    $connection = $installer->getConnection();
    $connection->addColumn( $resource->getTableName('core_store_group'), 'warehouse_id', array(
            'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
            'length' => 11,
            'nullable' => true,
            'comment' => 'Associated this store group to a warehouse ID'
    ));
} catch (Exception $e) {
    $readConnection = $resource->getConnection('core_read');
    $writeConnection = $resource->getConnection('core_write');
    $result = $readConnection->fetchAll("SHOW COLUMNS FROM " . $resource->getTableName('core_store_group') . " LIKE 'warehouse_id'");
    if (count($result) == 0) {
        $installer->run("
                ALTER TABLE {$this->getTable('core_store_group')}
                ADD warehouse_id INT(11) UNSIGNED;
        ");
    }
}

$installer->endSetup();
