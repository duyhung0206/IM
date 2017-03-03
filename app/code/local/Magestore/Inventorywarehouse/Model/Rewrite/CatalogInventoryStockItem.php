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

/**
 * Inventorywarehouse Model
 * 
 * @category    Magestore
 * @package     Magestore_Inventorywarehouse
 * @author      Magestore Developer
 */
class Magestore_Inventorywarehouse_Model_Rewrite_CatalogInventoryStockItem extends Mage_CatalogInventory_Model_Stock_Item {

    public function getQty() {
        if(Mage::app()->getStore()->isAdmin()) {
            return parent::getQty();
        }
        if (Mage::getStoreConfig('inventoryplus/general/select_warehouse') != 4) {
            return parent::getQty();
        }
        $storeGroup = Mage::app()->getGroup();
        $warehouseId = $storeGroup->getWarehouseId();    
        
        if(!$warehouseId) {
            return parent::getQty();
        }

        if($this->getProduct() && $this->getProduct()->isComposite()){
            return parent::getQty();
        }
        
        $availableQtyInWarehouse = Mage::getModel('inventoryplus/warehouse_product')
                ->getCollection()
                ->addFieldToFilter('warehouse_id', array('eq' => $warehouseId))
                ->addFieldToFilter('product_id', array('eq' => $this->getProductId()))
                ->setPageSize(1)
                ->setCurPage(1)
                ->getFirstItem()
                ->getAvailableQty();   
        return $availableQtyInWarehouse;
    }
     
}