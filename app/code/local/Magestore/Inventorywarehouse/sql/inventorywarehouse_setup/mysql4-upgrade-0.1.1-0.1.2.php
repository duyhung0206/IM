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

$connection = $installer->getConnection();
$connection->addColumn($this->getTable('erp_inventory_warehouse'), 'storepickup_id', array(
        'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'length' => 11,
        'nullable' => true,
        'comment' => 'Associated Warehouse with Store in Storepickup extension'
));

$connection->addColumn($this->getTable('erp_inventory_warehouse'), 'storelocator_id', array(
        'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'length' => 11,
        'nullable' => true,
        'comment' => 'Associated Warehouse with Store in Storelocator extension'
));

$installer->endSetup();


