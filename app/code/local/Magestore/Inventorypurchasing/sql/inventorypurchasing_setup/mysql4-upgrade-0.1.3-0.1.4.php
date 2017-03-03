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
 * @category 	Magestore
 * @package 	Magestore_Inventorysupplier
 * @copyright 	Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license 	http://www.magestore.com/license-agreement.html
 */
/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();

$connection->addColumn(
        $this->getTable('erp_inventory_purchase_order'), 'paid_all', array(
    'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
    'length' => 1,
    'default' => 0,
    'comment' => 'paid all to supplier'
        )
);

$connection->addColumn(
        $this->getTable('erp_inventory_purchase_order'), 'send_mail', array(
    'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
    'length' => 1,
    'default' => 0,
    'comment' => 'send email to supplier'
        )
);

$connection->addColumn(
        $this->getTable('erp_inventory_purchase_order'), 'complete_before', array(
    'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
    'length' => 1,
    'default' => 0,
    'comment' => 'complete purchase order before receiving all products from supplier'
        )
);

$connection->addColumn(
        $this->getTable('erp_inventory_purchase_order'), 'shipping_tax', array(
    'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
    'length' => 1,
    'default' => 0,
    'comment' => 'tax calculation settings for shipping; 0: exclude tax, 1: include tax'
        )
);

$connection->addColumn(
        $this->getTable('erp_inventory_purchase_order'), 'discount_tax', array(
    'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
    'length' => 1,
    'default' => 0,
    'comment' => 'tax calculation settings for discount; 0: before discount, 1: after discount'
        )
);

$purchaseOrder = Mage::getModel('inventorypurchasing/purchaseorder')->getCollection();
foreach ($purchaseOrder as $pOrder) {
    if ($pOrder->getPaid() == 0) {
        $pOrder->setPaidAll(0);
    } else {
        if ($pOrder->getPaid() >= $pOrder->getTotalAmount()) {
            $pOrder->setPaidAll(1);
        } else {
            $pOrder->setPaidAll(2);
        }
    }
    try {
        $pOrder->save();
    } catch (Exception $e) {
        Mage::log($e->getMessage(), null, 'inventory_management.log');
    }
}


$installer->run("
    CREATE TABLE IF NOT EXISTS {$this->getTable('erp_inventory_draft_purchase_order')}(
        `draft_po_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `currency` varchar(10) NOT NULL,
        `change_rate` float NOT NULL DEFAULT 1,
        `sales_from` datetime NOT NULL,
        `sales_to` datetime NOT NULL,
        `daterange_type` varchar(20) NOT NULL,
        `forecast_to` datetime NOT NULL,
        `warehouses` text NOT NULL,
        `suppliers` text NOT NULL,
        `purchase_rate` float NOT NULL DEFAULT '1',
        `created_at` datetime NOT NULL,
        `created_by` varchar(255) NOT NULL,
        PRIMARY KEY(`draft_po_id`)
    )ENGINE=InnoDB DEFAULT CHARSET=utf8;    
    
    CREATE TABLE IF NOT EXISTS {$this->getTable('erp_inventory_draft_purchase_order_product')}(
        `draft_po_product_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `draft_po_id` int(11) unsigned NOT NULL,
        `product_id` int(11) unsigned NOT NULL,
        `supplier_id` int(11) unsigned NOT NULL,
        `purchase_more` decimal(12,4) unsigned NOT NULL default '0',
        `warehouse_purchase` text,
        PRIMARY KEY(`draft_po_product_id`),
        INDEX(`draft_po_id`),
        FOREIGN KEY (`draft_po_id`) REFERENCES {$this->getTable('erp_inventory_draft_purchase_order')}(`draft_po_id`) ON DELETE CASCADE ON UPDATE CASCADE
    )ENGINE=InnoDB DEFAULT CHARSET=utf8;  
    
");

        
$connection->addColumn(
        $this->getTable('erp_inventory_draft_purchase_order'), 'type', array(
    'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
    'length' => 1,
    'default' => 1,
    'comment' => 'where draft purchase order comes from'
        )
);        

$installer->endSetup();
