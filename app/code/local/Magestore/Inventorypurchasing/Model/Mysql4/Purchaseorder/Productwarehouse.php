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
 * Inventorywarehouse Resource Model
 * 
 * @category    Magestore
 * @package     Magestore_Inventorywarehouse
 * @author      Magestore Developer
 */
class Magestore_Inventorypurchasing_Model_Mysql4_Purchaseorder_Productwarehouse extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('inventorypurchasing/purchaseorder_productwarehouse', 'purchase_order_product_warehouse_id');
    }
    
    /**
     * Get qty data in warehouse PO product
     * 
     * @param int $poId
     * @param int $productId
     * @param int $warehouseId
     * @return array
     */
    public function getQtyData($poId, $productId, $warehouseId) {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
                ->from($this->getMainTable(),array('qty_order', 'qty_received', 'qty_returned'))
                ->where('purchase_order_id = :purchase_order_id')
                ->where('product_id = :product_id')
                ->where('warehouse_id = :warehouse_id');
        $bind = array(':purchase_order_id' => $poId,
            ':product_id' => $productId,
            ':warehouse_id' => $warehouseId
        );

        $query = $adapter->query($select, $bind);
        while ($row = $query->fetch()) {
            return $row;
        }
        return array();
    }
    
    /**
     * 
     * @param int $poId
     * @param int $productId
     * @param int $warehouseId
     * @return array
     */
    public function getItemData($poId, $productId = null, $warehouseId = null) {
        $results = array();
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
                ->from($this->getMainTable())
                ->where('purchase_order_id = :purchase_order_id');
        $bind = array(':purchase_order_id' => $poId);    
        if($productId) {
           $select->where('product_id = :product_id');
           $bind[':product_id'] = $productId;
        }        
        if($warehouseId) {
           $select->where('warehouse_id = :warehouse_id');
           $bind[':warehouse_id'] = $warehouseId;
        }
        $query = $adapter->query($select, $bind);
        while ($row = $query->fetch()) {
            $results[] = $row;
        }
        return $results;        
    }

}
