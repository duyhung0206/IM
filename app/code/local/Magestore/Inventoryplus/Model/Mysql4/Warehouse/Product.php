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

/**
 * Inventory Resource Model
 * 
 * @category    Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventoryplus_Model_Mysql4_Warehouse_Product extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('inventoryplus/warehouse_product', 'warehouse_product_id');
    }

    /**
     * Get product item in warehouse
     * 
     * @param int $productId
     * @param int $warehouseId
     * @return array
     */
    public function getItem($productId, $warehouseId) {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
                ->from($this->getMainTable())
                ->where('warehouse_id = :warehouse_id')
                ->where('product_id = :product_id');
        $bind = array(':warehouse_id' => $warehouseId, ':product_id' => $productId);

        $query = $adapter->query($select, $bind);
        while ($row = $query->fetch()) {
            return $row;
        }
    }

    /**
     * 
     * @param int $productId
     * @param int $warehouseId
     * @param float $available_qty
     * @param float $total_qty
     */
    public function insertItem($productId, $warehouseId, $available_qty, $total_qty) {
        $adapter = $this->_getWriteAdapter();
        $adapter->insert($this->getMainTable(), array(
            'warehouse_id' => $warehouseId,
            'product_id' => $productId,
            'available_qty' => $available_qty,
            'total_qty' => $total_qty
        ));
    }

    /**
     * 
     * @param int $productId
     * @param int $warehouseId
     * @param float $available_qty
     * @param float $total_qty
     */
    public function updateItem($productId, $warehouseId, $available_qty, $total_qty) {
        $adapter = $this->_getWriteAdapter();
        $updateCond = array(
            $adapter->quoteInto('warehouse_id = ?', $warehouseId),
            $adapter->quoteInto('product_id = ?', $productId)
        );
        $adapter->update($this->getMainTable(), array(
            'available_qty' => $available_qty,
            'total_qty' => $total_qty
                ), $updateCond);
    }

    /**
     * 
     * @param int $productId
     * @return float
     */
    public function getAvailabelQty($productId) {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
                ->from($this->getMainTable(), 'SUM(available_qty)')
                ->where('product_id = :product_id');
        $bind = array(':product_id' => $productId);
        return floatval($adapter->fetchOne($select, $bind));
    }

    /**
     * 
     * @param int $productId
     * @param float $qty
     */
    public function updateCatalogQty($productId, $qty) {
        $adapter = $this->_getWriteAdapter();
        $table = Mage::getResourceModel('cataloginventory/stock_item')->getMainTable();
        $updateCond = array(
            $adapter->quoteInto('product_id = ?', $productId)
        );
        $adapter->update($table, array('qty' => $qty), $updateCond);
    }
    
    /**
     * 
     * @param int $productId
     * @return float
     */
    public function getCatalogQty($productId) {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
                ->from($this->getTable('cataloginventory/stock_item'), 'qty')
                ->where('product_id = :product_id');
        $bind = array(':product_id' => $productId);
        return (float) $adapter->fetchOne($select, $bind);
    }

    /**
     * 
     * @param int $productId
     * @return array
     */
    public function loadByProductId($productId) {
        $result = array();
        $table = $this->getMainTable();
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()->from($table, array('total_qty', 'warehouse_id'))->where("product_id = :product_id");
        $bind = array(':product_id' => $productId);
        $query = $adapter->query($select, $bind);
        while ($row = $query->fetch()) {
            $result[] = $row;
        }
        return $result;
    }
    
    

}
