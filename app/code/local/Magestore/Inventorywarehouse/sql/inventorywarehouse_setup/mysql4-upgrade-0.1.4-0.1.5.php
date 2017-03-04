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
/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("
  
    DROP TABLE IF EXISTS {$this->getTable('erp_inventory_warehouse_sendstock_product_delivery')};
    CREATE TABLE {$this->getTable('erp_inventory_warehouse_sendstock_product_delivery')} (
        `sendstock_delivery_id` int(11) unsigned NOT NULL auto_increment,        
        `warehouse_sendstock_id` int(11) unsigned default NULL,
        `time` varchar(255) default '',
        `product_id` int(11) unsigned default NULL,
        `product_name` varchar(255) default '',
        `product_sku` varchar(255) default '',
        `qty_delivery` int(11) default 0,
        `created_by` varchar(255) default '',
        PRIMARY KEY  (`sendstock_delivery_id`),
        INDEX(`warehouse_sendstock_id`),
        FOREIGN KEY (`warehouse_sendstock_id`) REFERENCES {$this->getTable('erp_inventory_warehouse_sendstock')}(`warehouse_sendstock_id`) ON DELETE CASCADE ON UPDATE CASCADE
    
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    
");

$installer->getConnection()
    ->addColumn($installer->getTable('erp_inventory_warehouse_sendstock_product'), 'total_delivery', array(
        'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
        'length' => 11,
        'nullable' => true,
        'default' => 0,
        'comment' => 'total_delivery'
    ));
$installer->endSetup();