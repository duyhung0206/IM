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
 * Inventorysupplyneeds Resource Model
 * 
 * @category    Magestore
 * @package     Magestore_Inventorysupplyneeds
 * @author      Magestore Developer
 */
class Magestore_Inventorysupplyneeds_Model_Mysql4_Inventorysupplyneeds extends Mage_Core_Model_Mysql4_Abstract {

    protected $_helperClass = null;

    public function _construct() {
        $this->_init('inventorysupplyneeds/inventorysupplyneeds', 'inventorysupplyneeds_id');
    }

    public function getLastPurchasedSuppliers($productData) {
        $poProducts = Mage::getResourceModel('inventorypurchasing/purchaseorder_product_collection')
                ->addFieldToFilter('product_id', array('in' => array_keys($productData)))
                ->setOrder('purchase_order_product_id', 'DESC');
        $poProducts->getSelect()->join(array('po' => $poProducts->getTable('inventorypurchasing/purchaseorder')), 'main_table.purchase_order_id = po.purchase_order_id', array('supplier_id'));
        /* $poProducts->getSelect()->join(array('supplier' => $poProducts->getTable('inventorypurchasing/supplier')),
          'po.supplier_id = supplier.supplier_id',
          array('supplier_id')); */
        $poProducts->getSelect()->group('main_table.product_id');
        $poProducts->getSelect()->columns(array(
            'list_supplier' => new Zend_Db_Expr('GROUP_CONCAT(po.supplier_id SEPARATOR ",")')
        ));
        return $poProducts;
    }

    public function gridExportGetSupplierProductCollection($supplierSelected) {
        $collection = Mage::getModel('inventorypurchasing/supplier_product')->getCollection();
        $collection->addFieldToSelect('product_id');
        if (count($supplierSelected) > 1)
            $collection->addFieldToFilter('supplier_id', array('in' => $supplierSelected));
        else
            $collection->addFieldToFilter('supplier_id', $supplierSelected[0]);
        $collection->getSelect()->columns(array(
            'all_supplier_id' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT supplier_id SEPARATOR ",")')));
        $collection->getSelect()->group('product_id');
        return $collection;
    }

    public function getNumberDaysInstockCollection($hours, $from, $to) {
        $coreResource = Mage::getSingleton('core/resource');
        $wproducts = Mage::getResourceModel('catalog/product_collection');
        $wproducts->getSelect()
                ->join(
                        array('oos_history' => $coreResource->getTableName('erp_inventory_outofstock_tracking')), "e.entity_id=oos_history.product_id", array('outofstock_date',
                    'instock_date',
                    'number_days_instock' => new Zend_Db_Expr('(' . $hours . ' - SUM(TIMESTAMPDIFF(HOUR,GREATEST(outofstock_date,\'' . $from . '\'),IFNULL(instock_date,"' . $to . '"))))/24')
        ));
        $wproducts->getSelect()->group('e.entity_id');
        return $wproducts;
    }

    public function gridExportGetWarehouseProductCollection($warehouseSelected, $getSku) {
        if (count($warehouseSelected) == 1 && $getSku != true)
            $postfix = "_{$warehouseSelected[0]}";
        else
            $postfix = "";
        $coreResource = Mage::getSingleton('core/resource');
        $collection = Mage::getModel('inventoryplus/warehouse_product')->getCollection();
        $collection->addFieldToSelect('product_id');
        if (count($warehouseSelected) > 1)
            $collection->addFieldToFilter('main_table.warehouse_id', array('in' => $warehouseSelected));
        else
            $collection->addFieldToFilter('main_table.warehouse_id', $warehouseSelected[0]);
        $collection->getSelect()->group('main_table.product_id');
        $collection->getSelect()->columns(array(
            'total_available_qty' . $postfix => new Zend_Db_Expr('SUM(available_qty)'),
            'available_qty' . $postfix => 'available_qty'
        ));
        if (count($warehouseSelected) >= 1)
            $collection->getSelect()
                    ->joinLeft(
                            array('catalog_product_entity' => $coreResource->getTableName('catalog_product_entity')), "main_table.product_id = " . 'catalog_product_entity' . ".entity_id", array('sku'));
        return $collection;
    }

    public function gridExportGetOrderItemCollection($listItemIds, $salesFromTo, $warehouseSelected, $getSku) {
        $helperClass = $this->getHelperClass();
        $salesFromTo = $helperClass->getSalesFromTo();
        $coreResource = Mage::getSingleton('core/resource');
        $daysInStock = $this->getNumberDaysInstock($helperClass);
        $this->removeTempTables(array('days_instock_table'));
        $this->createTempTable('days_instock_table', $daysInStock);
        $days = $this->getNumberDaysFromTwoDate($salesFromTo['from'], $salesFromTo['to']);
        if (count($warehouseSelected) == 1 && $getSku != true)
            $postfix = "_{$warehouseSelected[0]}";
        else
            $postfix = "";
        $collection = Mage::getModel('sales/order_item')->getCollection();
        $collection->addFieldToSelect('*');
        $collection->getSelect()->where("item_id IN ({$listItemIds})");
        $collection->getSelect()
                ->joinLeft(
                        array('days_instock_templ' => $coreResource->getTableName('days_instock_table')), "main_table.product_id=days_instock_templ.entity_id", array('number_days_instock'));
        $collection->getSelect()->columns(array(
            'total_qty_ordered' . $postfix => new Zend_Db_Expr('SUM(qty_ordered)'),
            'avg_qty_ordered' . $postfix => new Zend_Db_Expr("ROUND(SUM(qty_ordered)/IFNULL(number_days_instock,'$days'),2)")));
        $collection->getSelect()->group('product_id');
        return $collection;
    }

    public function gridExportGetPOProductCollection($warehouseSelected, $supplierSelected, $getSku) {
        if (count($warehouseSelected) == 1 && $getSku != true)
            $postfix = "_{$warehouseSelected[0]}";
        else
            $postfix = "";
        $supplierSelectedStr = implode(',', $supplierSelected);
        $collection = Mage::getModel('inventorypurchasing/purchaseorder_productwarehouse')->getCollection();
        $collection->addFieldToSelect(array('product_id', 'purchase_order_id'));
        if (count($warehouseSelected) > 1)
            $collection->addFieldToFilter('main_table.warehouse_id', array('in' => $warehouseSelected));
        else
            $collection->addFieldToFilter('main_table.warehouse_id', $warehouseSelected[0]);

        $awaitingPOids = Mage::helper('inventorypurchasing/purchaseorder')->getAwaitingPOids();
        $awaitingPOids = implode("','", $awaitingPOids);

        $collection->getSelect()
                ->joinLeft(
                        array('purchase_order' => $collection->getTable('inventorypurchasing/purchaseorder')), "main_table.purchase_order_id = purchase_order.purchase_order_id AND purchase_order.purchase_order_id IN ('" . $awaitingPOids . "')", array('supplier_id'));

        if (count($supplierSelected) > 1)
            $collection->getSelect()->where("purchase_order.supplier_id IN ({$supplierSelectedStr})");
        else
            $collection->getSelect()->where("purchase_order.supplier_id = {$supplierSelectedStr}");
        $collection->getSelect()->columns(array(
            'in_purchasing' . $postfix => new Zend_Db_Expr('IFNULL(SUM(`qty_order` - `qty_received` + `qty_returned`),0)')));
        $collection->getSelect()->group('product_id');
        return $collection;
    }

    protected function getHelperClass() {
        $helperClass = $this->_helperClass;
        if (!$helperClass) {
            $filter = Mage::app()->getRequest()->getParam('top_filter');
            $helperClass = Mage::helper('inventorysupplyneeds');
            $helperClass->setTopFilter($filter);
            $this->_helperClass = $helperClass;
        }
        return $helperClass;
    }

    public function setHelperClass($helperClass) {
        $this->_helperClass = $helperClass;
    }

    protected function removeTempTables($tempTableArr) {
        $coreResource = Mage::getSingleton('core/resource');
        $sql = "";
        foreach ($tempTableArr as $tempTable) {
            $sql .= "DROP TABLE  IF EXISTS " . $coreResource->getTableName($tempTable) . ";";
        }
        $coreResource->getConnection('core_write')->query($sql);
        return;
    }

    protected function createTempTable($tempTable, $collection) {
        $coreResource = Mage::getSingleton('core/resource');
        $_temp_sql = "CREATE TEMPORARY TABLE " . $coreResource->getTableName($tempTable) . " ("; // CREATE TEMPORARY TABLE
        $_temp_sql .= $collection->getSelect()->__toString() . ");";
        $coreResource->getConnection('core_write')->query($_temp_sql);
    }

    protected function getNumberDaysInstock($helperClass) {
        $salesFromTo = $helperClass->getSalesFromTo();
        $from = $salesFromTo['from'];
        $to = $salesFromTo['to'];
        $hours = $this->getNumberHoursFromTwoDate($from, $to);
        $return = $this->getNumberDaysInstockCollection($hours, $from, $to);
        return $return;
    }

    protected function getNumberHoursFromTwoDate($from, $to) {
        $hours = round((strtotime($to) - strtotime($from)) / (60 * 60));
        return $hours;
    }

    public function gridGetSupplierProductCollection($helperClass) {
        $supplierSelected = $helperClass->getsupplierSelected();
        $collection = Mage::getModel('inventorypurchasing/supplier_product')->getCollection();
        $collection->addFieldToSelect('product_id');
        if (count($supplierSelected) > 1)
            $collection->addFieldToFilter('supplier_id', array('in' => $supplierSelected));
        else
            $collection->addFieldToFilter('supplier_id', $supplierSelected[0]);
        $collection->getSelect()->columns(array(
            'all_supplier_id' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT supplier_id SEPARATOR ",")')));
        $collection->getSelect()->group('product_id');
        return $collection;
    }

    public function gridGetNumberDaysInstock($helperClass) {
        $coreResource = Mage::getSingleton('core/resource');
        $salesFromTo = $helperClass->getSalesFromTo();
        $from = $salesFromTo['from'];
        $to = $salesFromTo['to'];
        $hours = $this->getNumberHoursFromTwoDate($from, $to);
        $wproducts = Mage::getResourceModel('catalog/product_collection');
        $wproducts->getSelect()
                ->join(
                        array('oos_history' => $coreResource->getTableName('erp_inventory_outofstock_tracking')), "e.entity_id=oos_history.product_id", array('outofstock_date',
                    'instock_date',
                    'number_days_instock' => new Zend_Db_Expr('(' . $hours . ' - SUM(TIMESTAMPDIFF(HOUR,GREATEST(outofstock_date,\'' . $from . '\'),IFNULL(instock_date,"' . $to . '"))))/24')
        ));
        $wproducts->getSelect()->group('e.entity_id');
        return $wproducts;
    }

    public function gridGetOrderItemCollection($listItemIds, $helperClass) {
        $salesFromTo = $helperClass->getSalesFromTo();
        $coreResource = Mage::getSingleton('core/resource');
        $daysInStock = $this->gridGetNumberDaysInstock($helperClass);
        $this->removeTempTables(array('days_instock_table'));
        $this->createTempTable('days_instock_table', $daysInStock);
        $days = $this->getNumberDaysFromTwoDate($salesFromTo['from'], $salesFromTo['to']);
        $collection = Mage::getModel('sales/order_item')->getCollection();
        $collection->addFieldToSelect('*');
        $collection->getSelect()->where("item_id IN ({$listItemIds})");
        $collection->getSelect()
                ->joinLeft(
                        array('days_instock_templ' => $coreResource->getTableName('days_instock_table')), "main_table.product_id=days_instock_templ.entity_id", array('number_days_instock'));
        $collection->getSelect()->columns(array(
            'total_qty_ordered' => new Zend_Db_Expr('SUM(qty_ordered)'),
            'avg_qty_ordered' => new Zend_Db_Expr("ROUND(SUM(qty_ordered)/IFNULL(number_days_instock,'$days'),2)")));
        $collection->getSelect()->group('main_table.product_id');
        return $collection;
    }

    public function gridGetPOProductCollection($helperClass) {
        $warehouseSelected = $helperClass->getWarehouseSelected();
        $supplierSelected = $helperClass->getsupplierSelected();
        $supplierSelectedStr = implode(',', $supplierSelected);
        $collection = Mage::getModel('inventorypurchasing/purchaseorder_productwarehouse')->getCollection();
        $collection->addFieldToSelect(array('product_id', 'purchase_order_id'));
        if (count($warehouseSelected) > 1)
            $collection->addFieldToFilter('main_table.warehouse_id', array('in' => $warehouseSelected));
        else
            $collection->addFieldToFilter('main_table.warehouse_id', $warehouseSelected[0]);
        $awaitingPOids = Mage::helper('inventorypurchasing/purchaseorder')->getAwaitingPOids();
        $awaitingPOids = implode("','", $awaitingPOids);
        $collection->getSelect()
                ->joinLeft(
                        array('purchase_order' => $collection->getTable('inventorypurchasing/purchaseorder')), "main_table.purchase_order_id = purchase_order.purchase_order_id AND purchase_order.purchase_order_id IN ('" . $awaitingPOids . "')", array('supplier_id'));
        if (count($supplierSelected) > 1)
            $collection->getSelect()->where("purchase_order.supplier_id IN ({$supplierSelectedStr})");
        else
            $collection->getSelect()->where("purchase_order.supplier_id = {$supplierSelectedStr}");
        $collection->getSelect()->columns(array(
            'in_purchasing' => new Zend_Db_Expr('IFNULL(SUM(qty_order - qty_received ),0)')));
        $collection->getSelect()->group('product_id');
        return $collection;
    }

    public function gridGetWarehouseProductCollection($helperClass) {
        $coreResource = Mage::getSingleton('core/resource');
        $warehouseSelected = $helperClass->getWarehouseSelected();
        $collection = Mage::getModel('inventoryplus/warehouse_product')->getCollection();
        $collection->addFieldToSelect(array('product_id'));
        if (count($warehouseSelected) > 1)
            $collection->addFieldToFilter('main_table.warehouse_id', array('in' => $warehouseSelected));
        else
            $collection->addFieldToFilter('main_table.warehouse_id', $warehouseSelected[0]);
        $collection->getSelect()->columns(array(
            'total_available_qty' => 'SUM(available_qty)'
        ));
        $collection->getSelect()
                ->joinLeft(
                        array('catalog_product_entity' => $coreResource->getTableName('catalog_product_entity')), "main_table.product_id = " . 'catalog_product_entity' . ".entity_id", array('product_sku' => 'sku'));
        $collection->getSelect()->group('main_table.product_id');
        return $collection;
    }

    public function _gridGilterNumberCallback($collection, $filter, $field) {
        if (isset($filter['from']) && $filter['from'] != '') {
            $collection->getSelect()->having($field . ' >= ' . $filter['from']);
        }
        if (isset($filter['to']) && $filter['to'] != '') {
            $collection->getSelect()->having($field . ' <= ' . $filter['to']);
        }
        $collection->setIsGroupCountSql(true);
        $collection->setResetHaving(true);
        return $collection;
    }

    public function _filterDateCallback($collection, $filter, $field) {
        if (isset($filter['from']) && $filter['from'] != '') {
            $from = Mage::getModel('core/date')->date('Y-m-d 00:00:00', $filter['from']);
            $collection->getSelect()->having($field . ' >= \'' . $from . '\'');
        }
        if (isset($filter['to']) && $filter['to'] != '') {
            $to = Mage::getModel('core/date')->date('Y-m-d 23:59:59', $filter['to']);
            $collection->getSelect()->having($field . ' <= \'' . $to . '\'');
        }
        $collection->setIsGroupCountSql(true);
        $collection->setResetHaving(true);
        return $collection;
    }

    protected function getNumberDaysFromTwoDate($from, $to) {
        $date1 = new DateTime($from);
        $date2 = new DateTime($to);
        $diff = $date2->diff($date1)->format("%a");
        return $diff;
    }

}
