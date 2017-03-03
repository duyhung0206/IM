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
class Magestore_Inventorywarehouse_Model_Mysql4_Inventorywarehouse extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('inventorywarehouse/inventorywarehouse', 'inventorywarehouse_id');
    }

    /**
     * @param $data
     */
    public function saveWarehouseShipment($data)
    {
        $this->_getWriteAdapter()->insertMultiple($this->getTable('erp_inventory_warehouse_shipment'), $data);
    }

    /**
     * @param $collection
     * @param $column
     */
    public function filter_product_name_callback($collection, $column)
    {
        $read = $this->_getReadAdapter();
        $value = $column->getFilter()->getValue();
        if (!is_null($value)) {
            $condition = $read->prepareSqlCondition('main_table.product_name', array('like' => '%' . $value . '%'));
            $collection->getSelect()->where($condition);
        }
    }

    /**
     * @param $productId
     * @param $avai
     * @return string
     */
    public function getSelectWarehouse($productId, $avai)
    {
        $read = $this->_getReadAdapter();
        if ($avai == 'MAX') {
            $exclusionSelect = $read->select()
                ->from(array('ts' => $this->getTable('inventoryplus/warehouse_product')), array('max_available' => 'MAX(available_qty)'))
                ->where('product_id = ?', $productId);
        } else {
            $exclusionSelect = $read->select()
                ->from(array('ts' => $this->getTable('inventoryplus/warehouse_product')), array('min_available' => 'MIN(available_qty)'))
                ->where('product_id = ?', $productId);
        }
        $condition = $read->prepareSqlCondition('available_qty', array('eq' => $exclusionSelect));
        $select = $read->select()
                       ->from(array('ts'=>$this->getTable('inventoryplus/warehouse_product')), array('warehouse_id'))
                       ->where('product_id = ?', $productId)
                       ->where($condition);
        return $read->fetchOne($select);
    }


    /**
     * @param $product_id
     * @param $orders
     * @return array
     */
    public function getQtyItem($product_id, $orderIds)
    {
        $adapter = $this->_getReadAdapter();
        $condition = $adapter->prepareSqlCondition('order_id', array('in' => $orderIds));
        $select = $adapter->select()
            ->from($this->getTable('sales/order_item'), array('parent_item_id', 'qty_refunded', 'qty_ordered', 'qty_canceled'))
            ->where($condition)
            ->where('product_id = ?', $product_id);
        $query = $adapter->query($select);
        $items = array();
        while ($row = $query->fetch()) {
            $items[] = $row;
        }
        return $items;
    }

    /**
     * @param $parentId
     * @return array
     */
    public function getQtyParentItem($parentId)
    {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from($this->getTable('sales/order_item'), array('product_type', 'qty_ordered', 'qty_refunded', 'qty_canceled'))
            ->where('item_id = ?', $parentId);
        $query = $adapter->query($select);
        $parent = array();
        while ($row = $query->fetch()) {
            $parent[] = $row;
        }
        return $parent;
    }

    /**
     * @param $productId
     * @param $warehouseId
     * @param $orderIds
     * @return array
     */
    public function getWarehouseOrder($productId, $warehouseId, $orderIds)
    {
        $adapter = $this->_getReadAdapter();
        $condition = $adapter->prepareSqlCondition('order_id', array('in' => $orderIds));
        $select = $adapter->select()
            ->from($this->getTable('inventoryplus/warehouse_shipment'), 'order_id')
            ->where($condition)
            ->where('warehouse_id = ?', $warehouseId)
            ->where('product_id = ?', $productId);
        $query = $adapter->query($select);
        $warehouseOrder = array();
        while ($row = $query->fetch()) {
            $warehouseOrder[] = $row;
        }
        return $warehouseOrder;
    }
}