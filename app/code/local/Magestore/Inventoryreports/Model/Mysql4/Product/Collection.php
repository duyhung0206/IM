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
 * @package     Magestore_Inventorysupplyneeds
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Inventoryreports Resource Collection Model
 * 
 * @category    Magestore
 * @package     Magestore_Inventoryreports
 * @author      Magestore Developer
 */
class Magestore_Inventoryreports_Model_Mysql4_Product_Collection 
    extends Magestore_Inventoryplus_Model_Mysql4_Product_Collection {
    
    public function setReportCollectionProductByWarehouse($warehouseId,$dateFrom, $dateTo){
        $this->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('status')
            ->addAttributeToSelect('price')
            ->addAttributeToSelect('attribute_set_id')
            ->addAttributeToSelect('type_id')
            ->addAttributeToFilter('type_id', array('nin' => array('configurable', 'bundle', 'grouped')));
        $this->getSelect()
            ->join(array('warehouse_shipment' => $this->getTable('inventoryplus/warehouse_shipment')), '`warehouse_shipment`.`product_id` = `e`.`entity_id` and `warehouse_shipment`.`warehouse_id` = \''. $warehouseId .'\'',
                array('total_order' => 'sum(`warehouse_shipment`.`qty_shipped`)')
            )
            ->join(array('order_shipment' => $this->getTable('sales/shipment')), '`warehouse_shipment`.`shipment_id` = `order_shipment`.`entity_id` and `order_shipment`.`created_at` >= \''. $dateFrom .'\' and `order_shipment`.`created_at` <= \''. $dateTo.'\'',
                array('')
            );
        $this->groupBy('e.entity_id');
        $this->setIsGroupCountSql(true);
    }
    
    public function setReportCollectionProductAllWarehouse($dateFrom, $dateTo){
        $this->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('status')
            ->addAttributeToSelect('price')
            ->addAttributeToSelect('attribute_set_id')
            ->addAttributeToSelect('type_id')
            ->addAttributeToFilter('type_id', array('nin' => array('configurable', 'bundle', 'grouped')));
        $this->getSelect()
            ->join(
                array('aggregation' => $this->getResource()->getTable('sales/order_item')),
                "e.entity_id = aggregation.product_id AND aggregation.created_at BETWEEN '{$dateFrom}' AND '{$dateTo}'",
                array('SUM(aggregation.qty_ordered) AS sold_quantity')
            )
            ->order(array('sold_quantity DESC', 'e.created_at'));
        $this->groupBy('e.entity_id');
        $this->setIsGroupCountSql(true);
    }
    public function setCollectionTotalOrderedByWarehouse($warehouseId,$dateFrom,$dateTo){
        $this->addAttributeToFilter('type_id', array('nin' => array('configurable', 'bundle', 'grouped')));
        $this->getSelect()
            ->join(array('warehouse_shipment' => $this->getTable('inventoryplus/warehouse_shipment')), '`warehouse_shipment`.`product_id` = `e`.`entity_id` and `warehouse_shipment`.`warehouse_id` = \''. $warehouseId .'\'',
                array('total_order' => 'sum(`warehouse_shipment`.`qty_shipped`)')
            )
            ->join(array('order_shipment' => $this->getTable('sales/shipment')), '`warehouse_shipment`.`shipment_id` = `order_shipment`.`entity_id` and `order_shipment`.`created_at` >= \''. $dateFrom .'\' and `order_shipment`.`created_at` <= \''. $dateTo.'\'',
                array('')
            );
        $this->groupBy('e.entity_id');
    }
    public function setCollectionTotalOrderedAllWarehouse($dateFrom,$dateTo){
        $this->addAttributeToFilter('type_id', array('nin' => array('configurable', 'bundle', 'grouped')));
        $this->getSelect()
            ->join(
                array('aggregation' => $this->getResource()->getTable('sales/order_item')),
                "e.entity_id = aggregation.product_id AND aggregation.created_at BETWEEN '{$dateFrom}' AND '{$dateTo}'",
                array('SUM(aggregation.qty_ordered) AS sold_quantity')
            );
        $this->groupBy('e.entity_id');
        $this->getSelect()->order(array('sold_quantity DESC', 'e.created_at'));
    }

    public function groupBy($field){
        $this->getSelect()->group($field);
    }

    public function having($having){
        $this->getSelect()->having($having);
    }

    public function addColumns($columns){
        $this->getSelect()->columns($columns);
    }

}
