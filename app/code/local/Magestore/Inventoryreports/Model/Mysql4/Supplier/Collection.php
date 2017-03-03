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

class Magestore_Inventoryreports_Model_Mysql4_Supplier_Collection
    extends Magestore_Inventorypurchasing_Model_Mysql4_Supplier_Collection
{
    public function getSalesSupplierReportCollection($datefrom, $dateto, $source) {
        Mage::log($source);
        $toTimezone = Mage::getSingleton('core/date')->getGmtOffset('hours') . ':00';
        if (Mage::getSingleton('core/date')->getGmtOffset('hours') >= 0)
            $toTimezone = '+' . $toTimezone;
        $fromTimezone = (date("Z") >= 0) ? '+' . date("Z") . ':00' : date("Z") . ':00';
        $this->getSelect()->where("CONVERT_TZ(order.created_at,'$fromTimezone','$toTimezone')  >= '$datefrom'");
        $this->getSelect()->where("CONVERT_TZ(order.created_at,'$fromTimezone','$toTimezone') <=  '$dateto'" );
        $this->getSelect()->joinLeft(
            array('supplierProduct' => $this->getTable('inventorypurchasing/supplier_product')), '`main_table`.`supplier_id` = `supplierProduct`.`supplier_id`', array('product_id')
        );
        $this->getSelect()->joinLeft(
            array('orderItem' => $this->getTable('sales/order_item')), '`supplierProduct`.`product_id` = `orderItem`.`product_id`', array('qty_ordered', 'row_total', 'base_row_total')
        );
        $this->getSelect()->joinLeft(
            array('order' => $this->getTable('sales/order')), '`orderItem`.`order_id` = `order`.`entity_id`', array('status')
        );
        $this->getSelect()->where('`orderItem`.`parent_item_id` is NULL');
        $this->groupBy("main_table.supplier_id");
        $this->setOrder(("IFNULL(SUM(`orderItem`.`row_total`),0)"), 'DESC');
        $currencyCode = Mage::app()->getStore()->getBaseCurrency()->getCode();
        $this->getSelect()->columns(array(
            'all_order_id' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT `order`.`entity_id` SEPARATOR ",")'),
            'count_entity_id' => new Zend_Db_Expr('COUNT(DISTINCT `order`.`entity_id`)'),
            'sum_base_tax_amount' => '0',
            'sum_tax_amount' => '0',
            'sum_grand_total' => new Zend_Db_Expr('IFNULL(SUM(`orderItem`.`row_total_incl_tax`),0)'),
            'sum_base_grand_total' => new Zend_Db_Expr('IFNULL( SUM(orderItem.base_row_total_incl_tax) - SUM(orderItem.base_discount_amount),0)'),
            'base_currency_code' => new Zend_Db_Expr("IFNULL(`order`.`base_currency_code`,'" . $currencyCode . "')"),
            'order_currency_code' => new Zend_Db_Expr("IFNULL(`order`.`order_currency_code`,'" . $currencyCode . "')"),
            'sum_qty_ordered' => new Zend_Db_Expr('IFNULL(SUM(`orderItem`.`qty_ordered`),0)'),
        ));
        return $this;
    }
}