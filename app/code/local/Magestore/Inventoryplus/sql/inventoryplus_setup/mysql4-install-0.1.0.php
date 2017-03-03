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
 * @package     Magestore_Inventory
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */
/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

/**
 * create inventory table
 */

$connection = $installer->getConnection();
 
if (Mage::helper('core')->isModuleEnabled('Magestore_Inventory')){

$installer->run("
         CREATE TABLE IF NOT EXISTS {$this->getTable('erp_inventory_warehouse')} (
                `warehouse_id` int(11) unsigned NOT NULL auto_increment,
                `warehouse_name` varchar(255) NOT NULL,
                `manager_name` varchar(255) NOT NULL,
                `manager_email` varchar(255) default NULL,
                `telephone` varchar(50) default NULL,
                `street` text,
                `city` varchar(255) default NULL,
                `country_id` char(3) default '',
                `state` varchar(255) default NULL,
                `state_id` int(11) NULL,
                `postcode` varchar(255) default NULL,	
                `created_by` varchar(255) default NULL,
                `created_at` date default NULL,
                `updated_by` varchar(255) default NULL,
                `updated_at` date default NULL,
                `status` tinyint(1) NOT NULL,	
                PRIMARY KEY  (`warehouse_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


        CREATE TABLE IF NOT EXISTS {$this->getTable('erp_inventory_warehouse_product')} (
                `warehouse_product_id` int(11) unsigned NOT NULL auto_increment,
                `warehouse_id` int(11) unsigned NOT NULL,
                `product_id` int(11) unsigned NOT NULL,
                `total_qty` decimal(10,0) default '0',
                `available_qty` decimal(10,0) default '0',
                PRIMARY KEY  (`warehouse_product_id`),
                UNIQUE KEY `warehouse_id` (`warehouse_id`,`product_id`),
                INDEX (`warehouse_id`),
                INDEX (`product_id`),
                FOREIGN KEY (`warehouse_id`) REFERENCES {$this->getTable('erp_inventory_warehouse')}(`warehouse_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (`product_id`) REFERENCES {$this->getTable('catalog/product')}(`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        
        CREATE TABLE IF NOT EXISTS {$this->getTable('erp_inventory_warehouse_order')} (
                `warehouse_order_id` int(11) unsigned NOT NULL auto_increment,
                `warehouse_id` int(11) unsigned NOT NULL,
                `order_id` int(11) unsigned NOT NULL,
                `item_id` int(11) unsigned NOT NULL,
                `product_id` int(11) unsigned NOT NULL,
                `qty` decimal(10,0) default '0',
                PRIMARY KEY  (`warehouse_order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        
        CREATE TABLE IF NOT EXISTS {$this->getTable('erp_inventory_adjuststock')} (
                `adjuststock_id` int(11) unsigned NOT NULL auto_increment,
                `warehouse_id` int(11) unsigned NOT NULL,
                `warehouse_name` varchar(255) NOT NULL,
                `reason` text NOT NULL,
                `created_by` varchar(255) default NULL,
                `created_at` date default NULL,
                `confirmed_by` varchar(255) default NULL,
                `confirmed_at` date default NULL,
                `status` tinyint(1) NOT NULL,	
                PRIMARY KEY(`adjuststock_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        
        CREATE TABLE IF NOT EXISTS {$this->getTable('erp_inventory_adjuststock_product')} (
                `adjuststock_product_id` int(11) unsigned NOT NULL auto_increment,
                `adjuststock_id` int(11) unsigned  NOT NULL,
                `product_id` int(11) unsigned  NOT NULL,
                `old_qty` decimal(10,0) default '0',
                `suggest_qty` decimal(10,0) default '0',
                `adjust_qty` decimal(10,0) default '0',
                PRIMARY KEY  (`adjuststock_product_id`),
                INDEX(`adjuststock_id`),
                FOREIGN KEY (`adjuststock_id`) REFERENCES {$this->getTable('erp_inventory_adjuststock')}(`adjuststock_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
   
        CREATE TABLE IF NOT EXISTS  {$this->getTable('erp_inventory_warehouse_permission')}(
                `warehouse_permission_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `warehouse_id` int(11) unsigned NOT NULL,
                `admin_id` int(11) unsigned NOT NULL,
                `can_edit_warehouse` tinyint(1) NOT NULL,
                `can_adjust` tinyint(1) NOT NULL,
                INDEX (`warehouse_id`),
                INDEX (`admin_id`),
                PRIMARY KEY(`warehouse_permission_id`),
                FOREIGN KEY (`warehouse_id`) REFERENCES {$this->getTable('erp_inventory_warehouse')}(`warehouse_id`) ON DELETE CASCADE ON UPDATE CASCADE
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;


        CREATE TABLE IF NOT EXISTS {$this->getTable('erp_inventory_warehouse_shipment')} (
                `warehouse_shipment_id` int(11) unsigned NOT NULL auto_increment,
                `warehouse_id` int(11) unsigned  NOT NULL,
                `warehouse_name` varchar(255) NOT NULL,
                `shipment_id` int(11) unsigned  NOT NULL,
                `order_id` int(11) unsigned  NOT NULL,
                `item_id` int(11) unsigned  NOT NULL,
                `product_id` int(11) unsigned  NOT NULL,
                `qty_shipped` int(11) NOT NULL default '0',
                `qty_refunded` int(11) NOT NULL default '0',
                `subtotal_shipped` decimal(12,4) unsigned NOT NULL default 0,
                PRIMARY KEY  (warehouse_shipment_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        
        CREATE TABLE IF NOT EXISTS {$this->getTable('erp_inventory_warehouse_history')} (
            `warehouse_history_id` int(11) unsigned NOT NULL auto_increment,
            `warehouse_id` int(11) unsigned NOT NULL,
            `time_stamp` datetime,
            `create_by` varchar(255) NOT NULL,
            INDEX (`warehouse_id`),
            FOREIGN KEY (`warehouse_id`) REFERENCES {$this->getTable('erp_inventory_warehouse')}(`warehouse_id`) ON DELETE CASCADE ON UPDATE CASCADE,
            PRIMARY KEY  (`warehouse_history_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


        CREATE TABLE IF NOT EXISTS {$this->getTable('erp_inventory_warehouse_history_content')} (
            `warehouse_history_content_id` int(11) unsigned NOT NULL auto_increment,
            `warehouse_history_id` int(11) unsigned NOT NULL,
            `field_name` varchar(255) NOT NULL,
            `old_value` text,
            `new_value` text,
            INDEX (`warehouse_history_id`),
            FOREIGN KEY (`warehouse_history_id`) REFERENCES {$this->getTable('erp_inventory_warehouse_history')}(`warehouse_history_id`) ON DELETE CASCADE ON UPDATE CASCADE,
            PRIMARY KEY  (`warehouse_history_content_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

  
        CREATE TABLE IF NOT EXISTS {$this->getTable('erp_inventory_products')} (
                `inventory_product_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `product_id` int(11) unsigned NOT NULL,
                `cost_price` decimal(12,4) unsigned NOT NULL default '0.0000',
                `last_update` datetime default NULL,
                INDEX (`product_id`),
                UNIQUE (`product_id`),
                PRIMARY KEY(`inventory_product_id`),
                FOREIGN KEY (`product_id`) REFERENCES {$this->getTable('catalog/product')}(`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;

        CREATE TABLE IF NOT EXISTS  {$this->getTable('erp_inventory_warehouse_history')} (
            `warehouse_history_id` int(11) unsigned NOT NULL auto_increment,
            `warehouse_id` int(11) unsigned NOT NULL,
            `time_stamp` datetime,
            `create_by` varchar(255) NOT NULL,
            INDEX (`warehouse_id`),
            FOREIGN KEY (`warehouse_id`) REFERENCES {$this->getTable('erp_inventory_warehouse')}(`warehouse_id`) ON DELETE CASCADE ON UPDATE CASCADE,
            PRIMARY KEY  (`warehouse_history_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;


        CREATE TABLE IF NOT EXISTS {$this->getTable('erp_inventory_warehouse_history_content')} (
            `warehouse_history_content_id` int(11) unsigned NOT NULL auto_increment,
            `warehouse_history_id` int(11) unsigned NOT NULL,
            `field_name` varchar(255) NOT NULL,
            `old_value` text,
            `new_value` text,
            INDEX (`warehouse_history_id`),
            FOREIGN KEY (`warehouse_history_id`) REFERENCES {$this->getTable('erp_inventory_warehouse_history')}(`warehouse_history_id`) ON DELETE CASCADE ON UPDATE CASCADE,
            PRIMARY KEY  (`warehouse_history_content_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        
        CREATE TABLE IF NOT EXISTS  {$this->getTable('erp_inventory_checkupdate')} (
            `checkupdate_id` int(11) unsigned NOT NULL auto_increment,
            `version` varchar(255),
            `inserted_data` int(11),   
            PRIMARY KEY  (`checkupdate_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");        
        
    $connection->addColumn($this->getTable('erp_inventory_warehouse'), 'created_at', 
            array('type' => Varien_Db_Ddl_Table::TYPE_DATE, 'default' => null, 'comment'=>'created_at'));
    
    $connection->addColumn($this->getTable('erp_inventory_warehouse'), 'updated_at', 
            array('type' => Varien_Db_Ddl_Table::TYPE_DATE, 'default' => null, 'comment'=>'updated_at'));
    
    $connection->addColumn($this->getTable('erp_inventory_warehouse'), 'updated_by', 
            array('type' => Varien_Db_Ddl_Table::TYPE_TEXT, 'length' => 255, 'default' => null, 'comment'=>'updated_by'));
    
    $connection->addColumn($this->getTable('erp_inventory_warehouse_product'), 'created_at', 
            array('type' => Varien_Db_Ddl_Table::TYPE_DATETIME, 'default' => null, 'comment'=>'created_at'));    
    
    $connection->addColumn($this->getTable('erp_inventory_warehouse_product'), 'updated_at', 
            array('type' => Varien_Db_Ddl_Table::TYPE_DATETIME, 'default' => null, 'comment'=>'updated_at'));  
    
    $connection->addColumn($this->getTable('erp_inventory_adjuststock'), 'confirmed_by', 
            array('type' => Varien_Db_Ddl_Table::TYPE_DATE, 'default' => null, 'comment'=>'confirmed_by')); 
    
    $connection->addColumn($this->getTable('erp_inventory_adjuststock'), 'confirmed_by', 
            array('type' => Varien_Db_Ddl_Table::TYPE_TEXT, 'length' => 255, 'default' => null, 'comment'=>'confirmed_by')); 
    
    $connection->addColumn($this->getTable('erp_inventory_adjuststock'), 'status', 
            array('type' => Varien_Db_Ddl_Table::TYPE_SMALLINT, 'length' => 1, 'default' => 0, 'comment'=>'status')); 
    
    $connection->addColumn($this->getTable('erp_inventory_adjuststock_product'), 'suggest_qty', 
            array('type' => Varien_Db_Ddl_Table::TYPE_DECIMAL, 'length' => '12,4', 'default' => 0, 'comment'=>'suggest_qty'));    
    
    $connection->addColumn($this->getTable('erp_inventory_warehouse_shipment'), 'subtotal_shipped', 
            array('type' => Varien_Db_Ddl_Table::TYPE_DECIMAL, 'length' => '12,4', 'default' => 0, 'comment'=>'subtotal_shipped')); 

    $connection->addColumn($this->getTable('erp_inventory_warehouse_order'), 'item_id', 
            array('type' => Varien_Db_Ddl_Table::TYPE_INTEGER, 'length' => 11, 'comment'=>'item_id'));     
    
    
    $connection->changeColumn($this->getTable('erp_inventory_warehouse', 'name', 'warehouse_name',
            array('type' => Varien_Db_Ddl_Table::TYPE_TEXT, 'length' => 255, 'nullable' => false, 'comment'=>'warehouse_name')));
    
    $connection->changeColumn($this->getTable('erp_inventory_warehouse_product', 'qty', 'total_qty',
            array('type' => Varien_Db_Ddl_Table::TYPE_DECIMAL, 'length' => '12,4', 'default' => 0, 'comment'=>'total_qty')));

    $connection->changeColumn($this->getTable('erp_inventory_warehouse_product', 'qty_available', 'available_qty',
            array('type' => Varien_Db_Ddl_Table::TYPE_DECIMAL, 'length' => '12,4', 'default' => 0, 'comment'=>'available_qty')));
    
    $connection->changeColumn($this->getTable('erp_inventory_adjuststock_product', 'adjuststockproduct_id', 'adjuststock_product_id',
            array('type' => Varien_Db_Ddl_Table::TYPE_INTEGER, 'length' => 11, 'nullable' => false, 'AUTO_INCREMENT' => true, 'UNSIGNED' => true, 'comment'=>'adjuststock_product_id')));
        
    $connection->changeColumn($this->getTable('erp_inventory_adjuststock', 'create_by', 'created_by',
            array('type' => Varien_Db_Ddl_Table::TYPE_TEXT, 'length' => 255, 'nullable' => false, 'comment'=>'created_by')));

    $installer->run("
        
        INSERT INTO {$this->getTable('erp_inventory_checkupdate')} (`version`,`inserted_data`) VALUES ('1.0',0);
            
        UPDATE {$this->getTable('erp_inventory_warehouse_product')} SET created_at = now() WHERE created_at IS NULL;
        UPDATE {$this->getTable('erp_inventory_warehouse_product')} SET updated_at = now() WHERE updated_at IS NULL;
        UPDATE {$this->getTable('erp_inventory_warehouse')} SET created_at = now() WHERE created_at IS NULL;
        UPDATE {$this->getTable('erp_inventory_adjuststock')} SET status = '1' WHERE status IS NULL;
          
        UPDATE {$this->getTable('erp_inventory_adjuststock')} SET confirmed_by = created_by WHERE confirmed_by IS NULL;
        UPDATE {$this->getTable('erp_inventory_adjuststock')} SET confirmed_at = created_at WHERE confirmed_at IS NULL;
          
    ");        
        
}else{

    $installer->run("
        DROP TABLE IF EXISTS {$this->getTable('erp_inventory_warehouse')};
        CREATE TABLE {$this->getTable('erp_inventory_warehouse')} (
                `warehouse_id` int(11) unsigned NOT NULL auto_increment,
                `warehouse_name` varchar(255) NOT NULL,
                `manager_name` varchar(255) NOT NULL,
                `manager_email` varchar(255) default NULL,
                `telephone` varchar(50) default NULL,
                `street` text,
                `city` varchar(255) default NULL,
                `country_id` char(3) default '',
                `state` varchar(255) default NULL,
                `state_id` int(11) NULL,
                `postcode` varchar(255) default NULL,	
                `created_by` varchar(255) default NULL,
                `created_at` date default NULL,
                `updated_by` varchar(255) default NULL,
                `updated_at` date default NULL,
                `status` tinyint(1) NOT NULL,	
                PRIMARY KEY  (`warehouse_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        DROP TABLE IF EXISTS {$this->getTable('erp_inventory_warehouse_product')};
        CREATE TABLE {$this->getTable('erp_inventory_warehouse_product')} (
                `warehouse_product_id` int(11) unsigned NOT NULL auto_increment,
                `warehouse_id` int(11) unsigned NOT NULL,
                `product_id` int(11) unsigned NOT NULL,
                `total_qty` decimal(10,0) default '0',
                `available_qty` decimal(10,0) default '0',
                `created_at` datetime default NULL,
                `updated_at` datetime default NULL,
                PRIMARY KEY  (`warehouse_product_id`),
                UNIQUE KEY `warehouse_id` (`warehouse_id`,`product_id`),
                INDEX (`warehouse_id`),
                INDEX (`product_id`),
                FOREIGN KEY (`warehouse_id`) REFERENCES {$this->getTable('erp_inventory_warehouse')}(`warehouse_id`) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (`product_id`) REFERENCES {$this->getTable('catalog/product')}(`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
   

        DROP TABLE IF EXISTS {$this->getTable('erp_inventory_warehouse_permission')};		
        CREATE TABLE {$this->getTable('erp_inventory_warehouse_permission')}(
                `warehouse_permission_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `warehouse_id` int(11) unsigned NOT NULL,
                `admin_id` int(11) unsigned NOT NULL,
                `can_edit_warehouse` tinyint(1) NOT NULL,
                `can_adjust` tinyint(1) NOT NULL,
                INDEX (`warehouse_id`),
                INDEX (`admin_id`),
                PRIMARY KEY(`warehouse_permission_id`),
                FOREIGN KEY (`warehouse_id`) REFERENCES {$this->getTable('erp_inventory_warehouse')}(`warehouse_id`) ON DELETE CASCADE ON UPDATE CASCADE
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;

        DROP TABLE IF EXISTS {$this->getTable('erp_inventory_warehouse_order')};
        CREATE TABLE {$this->getTable('erp_inventory_warehouse_order')} (
                `warehouse_order_id` int(11) unsigned NOT NULL auto_increment,
                `warehouse_id` int(11) unsigned NOT NULL,
                `order_id` int(11) unsigned NOT NULL,
                `item_id` int(11) unsigned NOT NULL,
                `product_id` int(11) unsigned NOT NULL,
                `qty` decimal(10,0) default '0',
                PRIMARY KEY  (`warehouse_order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        DROP TABLE IF EXISTS {$this->getTable('erp_inventory_warehouse_shipment')};
        CREATE TABLE {$this->getTable('erp_inventory_warehouse_shipment')} (
                `warehouse_shipment_id` int(11) unsigned NOT NULL auto_increment,
                `warehouse_id` int(11) unsigned  NOT NULL,
                `warehouse_name` varchar(255) NOT NULL,
                `shipment_id` int(11) unsigned  NOT NULL,
                `order_id` int(11) unsigned  NOT NULL,
                `item_id` int(11) unsigned  NOT NULL,
                `product_id` int(11) unsigned  NOT NULL,
                `qty_shipped` int(11) NOT NULL default '0',
                `qty_refunded` int(11) NOT NULL default '0',
                `subtotal_shipped` decimal(12,4) unsigned NOT NULL default 0,
                PRIMARY KEY  (warehouse_shipment_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        DROP TABLE IF EXISTS {$this->getTable('erp_inventory_warehouse_history')};
        CREATE TABLE {$this->getTable('erp_inventory_warehouse_history')} (
            `warehouse_history_id` int(11) unsigned NOT NULL auto_increment,
            `warehouse_id` int(11) unsigned NOT NULL,
            `time_stamp` datetime,
            `create_by` varchar(255) NOT NULL,
            INDEX (`warehouse_id`),
            FOREIGN KEY (`warehouse_id`) REFERENCES {$this->getTable('erp_inventory_warehouse')}(`warehouse_id`) ON DELETE CASCADE ON UPDATE CASCADE,
            PRIMARY KEY  (`warehouse_history_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        DROP TABLE IF EXISTS {$this->getTable('erp_inventory_warehouse_history_content')};
        CREATE TABLE {$this->getTable('erp_inventory_warehouse_history_content')} (
            `warehouse_history_content_id` int(11) unsigned NOT NULL auto_increment,
            `warehouse_history_id` int(11) unsigned NOT NULL,
            `field_name` varchar(255) NOT NULL,
            `old_value` text,
            `new_value` text,
            INDEX (`warehouse_history_id`),
            FOREIGN KEY (`warehouse_history_id`) REFERENCES {$this->getTable('erp_inventory_warehouse_history')}(`warehouse_history_id`) ON DELETE CASCADE ON UPDATE CASCADE,
            PRIMARY KEY  (`warehouse_history_content_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        DROP TABLE IF EXISTS {$this->getTable('erp_inventory_adjuststock')};
        CREATE TABLE {$this->getTable('erp_inventory_adjuststock')} (
                `adjuststock_id` int(11) unsigned NOT NULL auto_increment,
                `warehouse_id` int(11) unsigned NOT NULL,
                `warehouse_name` varchar(255) NOT NULL,
                `reason` text NOT NULL,
                `created_by` varchar(255) default NULL,
                `created_at` date default NULL,
                `confirmed_by` varchar(255) default NULL,
                `confirmed_at` date default NULL,
                `status` tinyint(1) NOT NULL,	
                PRIMARY KEY(`adjuststock_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        DROP TABLE IF EXISTS {$this->getTable('erp_inventory_adjuststock_product')};
        CREATE TABLE {$this->getTable('erp_inventory_adjuststock_product')} (
                `adjuststock_product_id` int(11) unsigned NOT NULL auto_increment,
                `adjuststock_id` int(11) unsigned  NOT NULL,
                `product_id` int(11) unsigned  NOT NULL,
                `old_qty` decimal(10,0) default '0',
                `suggest_qty` decimal(10,0) default '0',
                `adjust_qty` decimal(10,0) default '0',
                PRIMARY KEY  (`adjuststock_product_id`),
                INDEX(`adjuststock_id`),
                FOREIGN KEY (`adjuststock_id`) REFERENCES {$this->getTable('erp_inventory_adjuststock')}(`adjuststock_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        DROP TABLE IF EXISTS {$this->getTable('erp_inventory_products')};		
        CREATE TABLE {$this->getTable('erp_inventory_products')} (
                `inventory_product_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `product_id` int(11) unsigned NOT NULL,
                `cost_price` decimal(12,4) unsigned NOT NULL default '0.0000',
                `last_update` datetime default NULL,
                INDEX (`product_id`),
                UNIQUE (`product_id`),
                PRIMARY KEY(`inventory_product_id`),
                FOREIGN KEY (`product_id`) REFERENCES {$this->getTable('catalog/product')}(`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE
        )ENGINE=InnoDB DEFAULT CHARSET=utf8;

        DROP TABLE IF EXISTS {$this->getTable('erp_inventory_checkupdate')};
        CREATE TABLE IF NOT EXISTS  {$this->getTable('erp_inventory_checkupdate')} (
            `checkupdate_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `version` varchar(255),
            `inserted_data` int(11),   
            PRIMARY KEY  (`checkupdate_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

        INSERT INTO {$this->getTable('erp_inventory_checkupdate')} (`version`,`inserted_data`) VALUES ('1.0', 1);
	
        DROP TABLE IF EXISTS {$this->getTable('erp_inventory_warehouse_transaction')};
        CREATE TABLE {$this->getTable('erp_inventory_warehouse_transaction')} (
            `warehouse_transaction_id` int(11) unsigned NOT NULL auto_increment,
            `warehouse_sendstock_id` int(11) unsigned default NULL,
            `warehouse_requeststock_id` int(11) unsigned default NULL,
            `type` tinyint(1) NOT NULL default '1',
            `warehouse_id_from` int(11) unsigned default NULL,
            `warehouse_name_from` varchar(255) default '',
            `warehouse_id_to` int(11) unsigned default NULL,
            `warehouse_name_to` varchar(255) default '',
            `total_products` decimal(10,0) default '0',
            `created_at` date default NULL,
            `created_by` varchar(255) default '',
            `reason` text default '',
            PRIMARY KEY  (`warehouse_transaction_id`)       
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	DROP TABLE IF EXISTS {$this->getTable('erp_inventory_warehouse_transaction_product')};	
        CREATE TABLE IF NOT EXISTS {$this->getTable('erp_inventory_warehouse_transaction_product')} (
            `warehouse_transaction_product_id` int(11) unsigned NOT NULL auto_increment,
            `warehouse_transaction_id` int(11) unsigned default NULL,
            `product_id` int(11) unsigned default NULL,
            `product_sku` varchar(255) default '',
            `product_name` varchar(255) default '',
            `qty` decimal(10,0) default '0',        
            PRIMARY KEY  (`warehouse_transaction_product_id`),
            INDEX(`warehouse_transaction_id`),
            FOREIGN KEY (`warehouse_transaction_id`) REFERENCES {$this->getTable('erp_inventory_warehouse_transaction')}(`warehouse_transaction_id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;		
    ");
}

$installer->endSetup();

