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
 * Inventory Resource Collection Model
 * 
 * @category    Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventorypurchasing_Model_Mysql4_Product_Collection extends Magestore_Inventoryplus_Model_Mysql4_Product_Collection {

    const LOW_STOCK_QTY_XML_PATH = 'cataloginventory/item_options/notify_stock_qty';

    /**
     * Get low stock product collection
     * 
     * @param array $productIds
     * @param array $warehouseIds
     * @param array $supplierIds
     * @return \Magestore_Inventorypurchasing_Model_Mysql4_Product_Collection
     */
    public function getLowStockCollection($productIds, $warehouseIds, $supplierIds) {
        $lowStockQty = Mage::getStoreConfig(self::LOW_STOCK_QTY_XML_PATH);
        $awaitingPOids = Mage::helper('inventorypurchasing/purchaseorder')->getAwaitingPOids($supplierIds);
        $awaitingPOids = implode("','", $awaitingPOids);

        $this->addAttributeToSelect('name')
                ->addFieldToFilter('entity_id', array('in' => $productIds));

        $this->getSelect()
                ->joinLeft(
                        array('stockItem' => $this->getTable('cataloginventory/stock_item')), 'e.entity_id = stockItem.product_id AND stockItem.stock_id = 1', array('qty', 'use_config_notify_stock_qty', 'notify_stock_qty')
        );

        /* join to warehouse_product */
        $this->getSelect()
                ->joinLeft(array('productWH' => $this->getTable('inventoryplus/warehouse_product')), 'e.entity_id = productWH.product_id '
                        . 'AND productWH.warehouse_id IN (\'' . implode("','", $warehouseIds) . '\')', array('available_qty', 'warehouse_id'));
        /* join to supplier_product */
        $this->getSelect()
                ->join(array('sProduct' => $this->getTable('inventorypurchasing/supplier_product'), 'e.entity_id = sProduct.product_id '
                    . ' AND sProduct.supplier_id IN (\'' . implode("','", $supplierIds) . '\')'));
        /* join to get purchasing qty */
        $this->getSelect()
                ->joinLeft(array('poProductWH' => $this->getTable('inventorypurchasing/purchaseorder_productwarehouse')), 'poProductWH.purchase_order_id IN (\'' . $awaitingPOids . '\') '
                        . 'AND (poProductWH.qty_order - poProductWH.qty_received) > 0 '
                        . 'AND e.entity_id = poProductWH.product_id '
                        . 'AND productWH.warehouse_id = poProductWH.warehouse_id ', array('qty_order', 'qty_received'));

        $this->getSelect()->group('productWH.warehouse_product_id');
        $this->setIsGroupCountSql(true);
        $this->getSelect()->columns(array(
            'lowstock_qty' => new Zend_Db_Expr("IF(stockItem.use_config_notify_stock_qty = 1, $lowStockQty, stockItem.notify_stock_qty)"),
            'purchasing_qty' => new Zend_Db_Expr('SUM(IFNULL(poProductWH.qty_order - poProductWH.qty_received, 0))'),
            'purchase_qty' => new Zend_Db_Expr("IF(stockItem.use_config_notify_stock_qty = 1, $lowStockQty, stockItem.notify_stock_qty) - productWH.available_qty - SUM(IFNULL(poProductWH.qty_order - poProductWH.qty_received, 0))"),
        ));
        return $this;
    }

    /**
     * 
     * @param array $productIds
     * @param array $warehouseIds
     * @param float $lowStockQty
     * @param array $supplierIds
     * @return \Magestore_Inventorypurchasing_Model_Mysql4_Product_Collection
     */
    public function getLowStockByProductIds($productIds, $warehouseIds, $lowStockQty, $supplierIds) {
        $awaitingPOids = Mage::helper('inventorypurchasing/purchaseorder')->getAwaitingPOids($supplierIds);
        $awaitingPOids = implode("','", $awaitingPOids);
        $this->addAttributeToSelect('name')
                ->addFieldToFilter('entity_id', array('in' => $productIds));

        $this->getSelect()
                ->joinLeft(
                        array('stockItem' => $this->getTable('cataloginventory/stock_item')), 'e.entity_id = stockItem.product_id AND stockItem.stock_id = 1', array('qty', 'use_config_notify_stock_qty', 'notify_stock_qty')
        );

        /* join to warehouse_product  */
        $this->getSelect()
                ->joinLeft(array('productWH' => $this->getTable('inventoryplus/warehouse_product')), 'e.entity_id = productWH.product_id '
                        . 'AND productWH.warehouse_id IN (\'' . implode("','", $warehouseIds) . '\')', array('available_qty'));

        /* join to get purchasing qty */
        $this->getSelect()
                ->joinLeft(array('poProductWH' => $this->getTable('inventorypurchasing/purchaseorder_productwarehouse')), 'poProductWH.purchase_order_id IN (\'' . $awaitingPOids . '\') '
                        . 'AND (poProductWH.qty_order - poProductWH.qty_received) > 0 '
                        . 'AND e.entity_id = poProductWH.product_id '
                        . 'AND productWH.warehouse_id = poProductWH.warehouse_id ', array('qty_order', 'qty_received'));

        $this->getSelect()->group('e.entity_id');
        $this->setIsGroupCountSql(true);
        $this->getSelect()->columns(array(
            'available_qty' => new Zend_Db_Expr("SUM(productWH.available_qty)"),
            'lowstock_qty' => new Zend_Db_Expr("SUM(IF(stockItem.use_config_notify_stock_qty = 1, $lowStockQty, stockItem.notify_stock_qty))"),
            'purchasing_qty' => new Zend_Db_Expr('SUM(IFNULL(poProductWH.qty_order - poProductWH.qty_received, 0))'),
            'purchase_qty' => new Zend_Db_Expr("SUM(IF(stockItem.use_config_notify_stock_qty = 1, $lowStockQty, stockItem.notify_stock_qty) - productWH.available_qty) - SUM(IFNULL(poProductWH.qty_order - poProductWH.qty_received, 0))"),
        ));

        /* filter low stocks */
        $this->getSelect()->having(new Zend_Db_Expr("SUM(IF(stockItem.use_config_notify_stock_qty = 1, $lowStockQty, stockItem.notify_stock_qty) - productWH.available_qty) - SUM(IFNULL(poProductWH.qty_order - poProductWH.qty_received, 0))") . "> '0'");
        return $this;
    }

    /**
     * 
     * @param int $poId
     * @param array $productIds
     * @return \Magestore_Inventorypurchasing_Model_Mysql4_Product_Collection
     */
    public function getReturnCollection($poId, $productIds) {
        $this->addAttributeToSelect('*')
                ->addFieldToFilter('entity_id', array('in' => $productIds))
                ->setIsGroupCountSql(true);
        $this->addStoreFilter(0);
        $this->getSelect()
                ->joinLeft(array('purchaseorderproduct' => $this->getTable('erp_inventory_purchase_order_product')), 'purchaseorderproduct.purchase_order_id IN (' . $poId . ') AND e.entity_id=purchaseorderproduct.product_id ', array(
                    'cost' => 'purchaseorderproduct.cost',
                    'tax' => 'purchaseorderproduct.tax',
                    'discount' => 'purchaseorderproduct.discount',
                    'qty' => 'purchaseorderproduct.qty',
                    'qty_recieved' => 'purchaseorderproduct.qty_recieved',
                    'qty_returned' => 'purchaseorderproduct.qty_returned'
                        )
                )
                ->group('e.entity_id');
        return $this;
    }

}
