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

/**
 * Inventorysupplier Resource Collection Model
 * 
 * @category 	Magestore
 * @package 	Magestore_Inventorysupplier
 * @author  	Magestore Developer
 */
class Magestore_Inventorypurchasing_Model_Mysql4_Supplier_Product_Collection 
    extends Magestore_Inventoryplus_Model_Mysql4_Collection_Abstract {

    public function _construct() {
        parent::_construct();
        $this->_init('inventorypurchasing/supplier_product');
    }
    
    /**
     * 
     * @param int $supplierId
     * @return \Magestore_Inventorypurchasing_Model_Mysql4_Supplier_Product_Collection
     */
    public function getStockOnHand($supplierId) {
        $this->addFieldToFilter('supplier_id', $supplierId);
        $this->getSelect()
                ->joinLeft(
                        array('warehouse_product' => $this->getTable('inventoryplus/warehouse_product')), "main_table.product_id = warehouse_product.product_id", array('total_qty'));
        $this->getSelect()->group(array('main_table.product_id'));
        $this->getSelect()->columns(array('total_qty_by_product' => 'sum(`warehouse_product`.`total_qty`)'));
        $catalog_product_entity = $this->getTable('catalog/product');
        $this->getSelect()
                ->joinLeft(
                        array($catalog_product_entity), "main_table.product_id = {$catalog_product_entity}.entity_id", array('sku'));
        $productAttributes = array('product_name' => 'name', 'product_cost' => 'cost');
        foreach ($productAttributes as $alias => $attributeCode) {
            $tableAlias = $attributeCode . '_table';
            $attribute = Mage::getSingleton('eav/config')
                    ->getAttribute(Mage_Catalog_Model_Product::ENTITY, $attributeCode);
            $this->getSelect()->joinLeft(
                    array($tableAlias => $attribute->getBackendTable()), "main_table.product_id = $tableAlias.entity_id AND $tableAlias.attribute_id={$attribute->getId()}", array($alias => 'value')
            );
        }
        $this->setOrder('total_qty_by_product', 'DESC');   
        return $this;
    }
}