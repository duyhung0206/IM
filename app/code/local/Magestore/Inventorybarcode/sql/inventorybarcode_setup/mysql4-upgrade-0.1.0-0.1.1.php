<?php

/**
 * Magestore
 * 
 * Online Magento Course
 * 
 */
/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

$connection = $installer->getConnection();

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'productname_show',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'length' => 1,
        'default' => 0,
        'comment' => 'Show product name in barcode'
    )
);

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'sku_show',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'length' => 1,
        'default' => 0,
        'comment' => 'Show SKU in barcode'
    )
);

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'price_show',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'length' => 1,
        'default' => 0,
        'comment' => 'Show price in barcode'
    )
);

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'barcode_per_row',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'length' => 2,
        'default' => 0,
        'comment' => 'Total barcode per row'
    )
);

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'barcode_unit',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'length' => 255,
        'default' => 0,
        'comment' => 'distant unit'
    )
);

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'page_width',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_FLOAT,
        'length' => 5,
        'default' => 0,
        'comment' => 'Paper width'
    )
);

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'page_height',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_FLOAT,
        'length' => 5,
        'default' => 0,
        'comment' => 'Paper height'
    )
);

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'veltical_distantce',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_FLOAT,
        'length' => 5,
        'default' => 0,
        'comment' => 'Row space'
    )
);

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'horizontal_distance',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_FLOAT,
        'length' => 5,
        'default' => 0,
        'comment' => 'Cell space'
    )
);

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'barcode_width',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_FLOAT,
        'length' => 5,
        'default' => 0,
        'comment' => 'barcode_width'
    )
);

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'barcode_height',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_FLOAT,
        'length' => 5,
        'default' => 0,
        'comment' => 'barcode_height'
    )
);

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'font_size',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_FLOAT,
        'length' => 5,
        'default' => 0,
        'comment' => 'font_size'
    )
);

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'top_margin',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_FLOAT,
        'length' => 5,
        'default' => 0,
        'comment' => 'top_margin'
    )
);

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'left_margin',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_FLOAT,
        'length' => 5,
        'default' => 0,
        'comment' => 'left_margin'
    )
);

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'right_margin',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_FLOAT,
        'length' => 5,
        'default' => 0,
        'comment' => 'right_margin'
    )
);

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'bottom_margin',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_FLOAT,
        'length' => 5,
        'default' => 0,
        'comment' => 'bottom_margin'
    )
);

$connection->addColumn(
    $this->getTable('erp_inventory_barcode_template'),
    'barcode_type',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_SMALLINT,
        'length' => 2,
        'default' => 0,
        'comment' => 'barcode_type'
    )
);

$model=Mage::getModel('inventorybarcode/barcodetemplate')->load('5');
try{
    $installer->run("
        UPDATE  {$this->getTable('erp_inventory_barcode_template')} SET `productname_show`= '0',`sku_show`= '0',`price_show`= '0',`barcode_per_row`= '3',`page_height`= '30',`barcode_unit`= '0',`veltical_distantce`= '0',`horizontal_distance`= '0',`page_width`= '120',`top_margin`= '0',`left_margin`= '0',`right_margin`= '0',`bottom_margin`= '0',`barcode_type`= '0',`barcode_width`= '39',`barcode_height`= '16',`font_size`= '2.4' WHERE `barcode_template_id` = 1;
        UPDATE  {$this->getTable('erp_inventory_barcode_template')} SET `productname_show`= '1',`sku_show`= '0',`price_show`= '0',`barcode_per_row`= '3',`page_height`= '30',`barcode_unit`= '0',`veltical_distantce`= '0',`horizontal_distance`= '0',`page_width`= '120',`top_margin`= '0',`left_margin`= '0',`right_margin`= '0',`bottom_margin`= '0',`barcode_type`= '0',`barcode_width`= '39',`barcode_height`= '18.4',`font_size`= '2.4' WHERE `barcode_template_id` = 2;
        UPDATE  {$this->getTable('erp_inventory_barcode_template')} SET `productname_show`= '1',`sku_show`= '0',`price_show`= '3',`barcode_per_row`= '3',`page_height`= '30',`barcode_unit`= '0',`veltical_distantce`= '0',`horizontal_distance`= '0',`page_width`= '120',`top_margin`= '0',`left_margin`= '0',`right_margin`= '0',`bottom_margin`= '0',`barcode_type`= '0',`barcode_width`= '39',`barcode_height`= '20.8',`font_size`= '2.4' WHERE `barcode_template_id` = 3;
        UPDATE  {$this->getTable('erp_inventory_barcode_template')} SET `productname_show`= '1',`sku_show`= '2',`price_show`= '0',`barcode_per_row`= '3',`page_height`= '30',`barcode_unit`= '0',`veltical_distantce`= '0',`horizontal_distance`= '0',`page_width`= '120',`top_margin`= '0',`left_margin`= '0',`right_margin`= '0',`bottom_margin`= '0',`barcode_type`= '0',`barcode_width`= '39',`barcode_height`= '20.8',`font_size`= '2.4' WHERE `barcode_template_id` = 4;
    ");
        $resource = Mage::getSingleton('core/resource');
        $write = $resource->getConnection('core_write');
        if($model->getId()){            
            $data = array("productname_show" => "1", "sku_show" => "2", "price_show" => "3", "barcode_per_row" => "1", "page_height" => "17", "barcode_unit" => "0", "veltical_distantce" => "0", "horizontal_distance" => "0", "page_width" => "76", "top_margin" => "0", "left_margin" => "0", "right_margin" => "0", "bottom_margin" => "0","barcode_type" => "1","barcode_width" => "26.5", "barcode_height" => "10.6","font_size" => "2"); 
            $where = "barcode_template_id = 5"; 
            $write->update($this->getTable('erp_inventory_barcode_template'), $data, $where); 
            
        }  else {
            $write->insert($this->getTable('erp_inventory_barcode_template'),
                array(
                    'barcode_template_id' => 5,
                    'barcode_template_name' => 'Barcode for jewelry',
                    'html' => Mage::getModel('inventorybarcode/barcodetemplate')->generateSampleBarcodeJewelryTemplateHtml(),
                    'template' => '',
                    'status' => 1,
                    'productname_show' => 1,
                    'sku_show' => 2,
                    'price_show' => 3,
                    'barcode_per_row' => '1',
                    'page_height' => '17',
                    'barcode_unit' => 0,
                    'veltical_distantce' => '0',
                    'horizontal_distance' => '0',
                    'page_width' => '76',
                    'top_margin' => '0',
                    'left_margin' => '0',
                    'right_margin' => '0',
                    'bottom_margin' => '0',
                    'barcode_type' => '1',
                    'barcode_width' => '26.5',
                    'barcode_height' => '10.6',
                    'font_size' => '2'
                )
            );
        }
        

} catch(Exception $e) {
    Mage::log($e->getMessage(), null, 'inventory_management.log');
}

$installer->run("
   	
    DROP TABLE IF EXISTS {$this->getTable('erp_inventory_barcode_scan_item')};
    CREATE TABLE {$this->getTable('erp_inventory_barcode_scan_item')} (
        `scanitem_id` int(11) unsigned NOT NULL auto_increment,
        `product_id` int(11) unsigned NOT NULL,
        `scan_qty` decimal(12,4) NULL,
        `user_id` int(11) unsigned NOT NULL,
        `last_scanned_at` datetime NULL,
        `is_finished` tinyint(1) default '0',
        `action` varchar(255) NOT NULL,
        PRIMARY KEY  (`scanitem_id`)                
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
");


$installer->endSetup();
