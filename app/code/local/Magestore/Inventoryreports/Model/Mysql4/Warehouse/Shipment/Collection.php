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
class Magestore_Inventoryreports_Model_Mysql4_Warehouse_Shipment_Collection
    extends Magestore_Inventoryplus_Model_Mysql4_Warehouse_Shipment_Collection
{
    public function getSalesWarehouseReportCollection($datefrom, $dateto, $source) {
        $toTimezone = Mage::getSingleton('core/date')->getGmtOffset('hours') . ':00';
        if (Mage::getSingleton('core/date')->getGmtOffset('hours') >= 0)
            $toTimezone = '+' . $toTimezone;
        $fromTimezone = (date("Z") >= 0) ? '+' . date("Z") . ':00' : date("Z") . ':00';
        $this->getSelect()->where("CONVERT_TZ(order.created_at,'$fromTimezone','$toTimezone')  >= '$datefrom'");
        $this->getSelect()->where("CONVERT_TZ(order.created_at,'$fromTimezone','$toTimezone') <=  '$dateto'" );

        $this->getSelect()->joinLeft(
            array('productSuper' => $this->getTable('catalog/product_super_link')), 'main_table.product_id= productSuper.product_id', array('parent_id')
        );
        $this->getSelect()->joinLeft(
            array('orderItem' => $this->getTable('sales/order_item')),
            'IFNULL(productSuper.parent_id,main_table.product_id) = orderItem.product_id '
            . ' AND main_table.order_id = orderItem.order_id',
            array('base_row_total_incl_tax', 'row_total_incl_tax', 'qty_ordered')
        );
        $this->getSelect()->join(
            array('order' => $this->getTable('sales/order')), 'orderItem.order_id = order.entity_id', array('status', 'created_at')
        );

        $this->groupBy("main_table.warehouse_id");
        $this->setOrder(("IFNULL(SUM(main_table.subtotal_shipped),0)"), 'DESC');
        $currencyCode = Mage::app()->getStore()->getBaseCurrency()->getCode();
        $this->getSelect()->columns(array(
            'all_order_id' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT `order`.`entity_id` SEPARATOR ",")'),
            'count_entity_id' => new Zend_Db_Expr('COUNT(DISTINCT `order`.entity_id)'),
            'sum_base_tax_amount' => '0',
            'sum_tax_amount' => '0',
            'sum_grand_total' => new Zend_Db_Expr('IFNULL( SUM(orderItem.row_total_incl_tax * (main_table.qty_shipped - main_table.qty_refunded) / orderItem.qty_ordered ),'
                . ' SUM(orderItem.row_total_incl_tax))'),
            'sum_base_grand_total' => new Zend_Db_Expr('IFNULL( SUM(orderItem.base_row_total_incl_tax * (main_table.qty_shipped - main_table.qty_refunded) / orderItem.qty_ordered ),'
                . ' SUM(orderItem.base_row_total_incl_tax))'),
            'base_currency_code' => new Zend_Db_Expr("IFNULL(`order`.`base_currency_code`,'" . $currencyCode . "')"),
            'order_currency_code' => new Zend_Db_Expr("IFNULL(`order`.`order_currency_code`,'" . $currencyCode . "')"),
            'sum_qty_ordered' => new Zend_Db_Expr('SUM(IFNULL(main_table.qty_shipped, orderItem.qty_ordered))'),
            'warehouse_name' => new Zend_Db_Expr('IFNULL(main_table.warehouse_name,\'' .'Unassigned Warehouse' . '\')')
        ));

        return $this;
    }
}
