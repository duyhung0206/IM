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
class Magestore_Inventoryreports_Model_Mysql4_Sales_Order_Item_Collection 
    extends Mage_Sales_Model_Mysql4_Order_Item_Collection{
    
    /**
     *
     * @var bool 
     */
    protected $_isGroupSql = false;
    
    /*
     * @var bool
     */
    protected $_resetHaving = false;

    /**
     * Set is grouping sql
     * 
     * @param bool $value
     * @return \Magestore_Inventoryreports_Model_Mysql4_Sales_Order_Collection
     */
    public function setIsGroupCountSql($value) {
        $this->_isGroupSql = $value;
        return $this;
    }

    /**
     * Reset having or not
     * 
     * @param bool $value
     * @return \Magestore_Inventoryreports_Model_Mysql4_Sales_Order_Collection
     */
    public function setResetHaving($value) {
        $this->_resetHaving = $value;
        return $this;
    }

    /**
     * Get collection size
     *
     * @return int
     */
    public function getSize() {
        if (is_null($this->_totalRecords)) {
            $sql = $this->getSelectCountSql();
            if ((count($this->getSelect()->getPart(Zend_Db_Select::GROUP)) > 0) || (count($this->getSelect()->getPart(Zend_Db_Select::HAVING)) > 0)) {
                $count = 0;
                $query = $this->getConnection()->query($sql, $this->_bindParams);
                while ($row = $query->fetch()) {
                    $count++;
                }
                $this->_totalRecords = $count;
            } else {
                $this->_totalRecords = ($this->getConnection()->fetchOne($sql, $this->_bindParams));
            }
        }
        return intval($this->_totalRecords);
    }

    /**
     * Get SQL for get record count
     *
     * @return Varien_Db_Select
     */
    public function getSelectCountSql() {
        $countSelect = clone $this->getSelect();
        $countSelect->reset(Zend_Db_Select::ORDER);
        $countSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $countSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
        //$countSelect->reset(Zend_Db_Select::HAVING);
        $countSelect->reset(Zend_Db_Select::COLUMNS);
        if ((count($this->getSelect()->getPart(Zend_Db_Select::GROUP)) > 0) || (count($this->getSelect()->getPart(Zend_Db_Select::HAVING)) > 0)) {
            //$countSelect->reset(Zend_Db_Select::GROUP);
            //$countSelect->distinct(true);
            $group = $this->getSelect()->getPart(Zend_Db_Select::GROUP);
            $countSelect->columns("COUNT(DISTINCT " . implode(", ", $group) . ")");
        } else {
            $countSelect->columns('COUNT(*)');
        }
        return $countSelect;
    }

    public function groupBy($field){
        $this->getSelect()->group($field);
    }

    public function getUnassignedWarehouseSalesCollection($requestData) {
        $dateRange = Mage::helper('inventoryreports')->getTimeRange($requestData);
        $dateto = $dateRange['to'];
        $datefrom = $dateRange['from'];
        $toTimezone = Mage::getSingleton('core/date')->getGmtOffset('hours') . ':00';
        if (Mage::getSingleton('core/date')->getGmtOffset('hours') >= 0)
            $toTimezone = '+' . $toTimezone;
        $fromTimezone = (date("Z") >= 0) ? '+' . date("Z") . ':00' : date("Z") . ':00';
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone')  >= '$datefrom'");
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone') <=  '$dateto'" );

        $this->getSelect()->joinLeft(
            array('productSuper' => $this->getTable('catalog/product_super_link')), 'main_table.product_id= productSuper.parent_id', array('product_id')
        );
        $this->getSelect()->joinLeft(
            array('warehouseShip' => $this->getTable('inventoryplus/warehouse_shipment')),
            'IFNULL(productSuper.product_id, main_table.product_id) = warehouseShip.product_id '
            . ' AND main_table.order_id = warehouseShip.order_id',
            array('qty_shipped', 'qty_refunded', 'warehouse_name')
        );

        $this->getSelect()->joinLeft(
            array('order' => $this->getTable('sales/order')), '`main_table`.`order_id` = `order`.`entity_id`', array('status')
        );

        $this->getSelect()->where('`warehouseShip`.`warehouse_id` is NULL');

        $currencyCode = Mage::app()->getStore()->getBaseCurrency()->getCode();
        $this->getSelect()->columns(array(
            'all_order_id' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT `order`.`entity_id` SEPARATOR ",")'),
            'count_entity_id' => new Zend_Db_Expr('COUNT(DISTINCT order.entity_id)'),
            'sum_base_tax_amount' => '0',
            'sum_tax_amount' => '0',
            'sum_grand_total' => new Zend_Db_Expr('IFNULL( SUM( orderItem.row_total_incl_tax * (warehouseShip.qty_shipped - warehouseShip.qty_refunded) / orderItem.qty_ordered ),'
                . ' SUM(orderItem.row_total_incl_tax))'),
            'sum_base_grand_total' => new Zend_Db_Expr('IFNULL( SUM( orderItem.base_row_total_incl_tax * (warehouseShip.qty_shipped - warehouseShip.qty_refunded) / orderItem.qty_ordered ),'
                . ' SUM(orderItem.base_row_total_incl_tax))'),
            'base_currency_code' => new Zend_Db_Expr("IFNULL(`order`.`base_currency_code`,'" . $currencyCode . "')"),
            'order_currency_code' => new Zend_Db_Expr("IFNULL(`order`.`order_currency_code`,'" . $currencyCode . "')"),
            'sum_qty_ordered' => new Zend_Db_Expr('SUM(IFNULL(warehouseShip.qty_shipped, orderItem.qty_ordered))'),
        ));
        //Filter by order status
       

        return $this;
    }

    public function getUnassignedSupplierSales($requestData) {
        $dateRange = Mage::helper('inventoryreports')->getTimeRange($requestData);
        $dateto = $dateRange['to'];
        $datefrom = $dateRange['from'];
        $toTimezone = Mage::getSingleton('core/date')->getGmtOffset('hours') . ':00';
        if (Mage::getSingleton('core/date')->getGmtOffset('hours') >= 0)
            $toTimezone = '+' . $toTimezone;
        $fromTimezone = (date("Z") >= 0) ? '+' . date("Z") . ':00' : date("Z") . ':00';
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone')  >= '$datefrom'");
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone') <=  '$dateto'" );


        $this->addFieldToFilter('main_table.sku', array('nin' => $this->getAssignedSupplierProductSkus()));
        $this->getSelect()->joinLeft(
            array('order' => $this->getTable('sales/order')), '`main_table`.`order_id` = `order`.`entity_id`', array('status')
        );

        $this->getSelect()->where('`main_table`.`parent_item_id` is NULL');

        $currencyCode = Mage::app()->getStore()->getBaseCurrency()->getCode();
        $this->getSelect()->columns(array(
            'all_order_id' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT `order`.`entity_id` SEPARATOR ",")'),
            'count_entity_id' => new Zend_Db_Expr('COUNT(DISTINCT `main_table`.`order_id`)'),
            'sum_grand_total' => new Zend_Db_Expr('IFNULL(SUM(`main_table`.`row_total_incl_tax`),0)'),
            'sum_base_grand_total' => new Zend_Db_Expr('IFNULL(SUM(`main_table`.`base_row_total_incl_tax`),0)'),
            'base_currency_code' => new Zend_Db_Expr("IFNULL(`order`.`base_currency_code`,'" . $currencyCode . "')"),
            'order_currency_code' => new Zend_Db_Expr("IFNULL(`order`.`order_currency_code`,'" . $currencyCode . "')"),
            'sum_qty_ordered' => new Zend_Db_Expr('IFNULL(SUM(`main_table`.`qty_ordered`),0)'),
        ));
        //Filter by order status
        
        return $this;
    }

    public function getSalesSKUReportCollection($datefrom, $dateto, $source) {
        Mage::log($source);
        $this->getSelect()->joinLeft(
            array('order' => $this->getTable('sales/order')), 'main_table.order_id = order.entity_id', array('status')
        );
        $toTimezone = Mage::getSingleton('core/date')->getGmtOffset('hours') . ':00';
        if (Mage::getSingleton('core/date')->getGmtOffset('hours') >= 0)
            $toTimezone = '+' . $toTimezone;
        $fromTimezone = (date("Z") >= 0) ? '+' . date("Z") . ':00' : date("Z") . ':00';
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone')  >= '$datefrom'");
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone') <=  '$dateto'" );
        /*
        $collection->getSelect()->joinLeft(
                array('productSuper' => $collection->getTable('catalog/product_super_link')), 'main_table.product_id= productSuper.product_id', array('parent_id')
        );
         */
        $this->getSelect()->joinLeft(
            array('product' => $this->getTable('catalog/product')), 'main_table.product_id = product.entity_id', array('sku')
        );

        $this->getSelect()->where('`main_table`.`parent_item_id` is NULL');
        $this->groupBy('product.entity_id');
        $this->setOrder(('IFNULL(SUM(main_table.row_total),0)'), 'DESC');
        $currencyCode = Mage::app()->getStore()->getBaseCurrency()->getCode();
        $this->getSelect()->columns(array(
            'all_order_id' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT main_table.order_id SEPARATOR ",")'),
            'count_entity_id' => new Zend_Db_Expr('COUNT(DISTINCT `main_table`.`order_id`)'),
            'sum_base_tax_amount' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_tax_amount),0)'),
            'sum_tax_amount' => new Zend_Db_Expr('IFNULL(SUM(main_table.tax_amount),0)'),
            'sum_discount_amount' => new Zend_Db_Expr('IFNULL(-SUM(main_table.base_discount_amount),0)'),
            'sum_row_total' => new Zend_Db_Expr('IFNULL(SUM(main_table.row_total),0)'),
            'sum_base_row_total' => new Zend_Db_Expr('IFNULL( SUM(main_table.base_row_total_incl_tax) - SUM(main_table.base_discount_amount),0)'),
            'sum_grand_total' => new Zend_Db_Expr('IFNULL(SUM(main_table.row_total_incl_tax),0)-IFNULL(SUM(main_table.discount_amount),0)'),
            'sum_base_grand_total' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_row_total_incl_tax),0)-IFNULL(SUM(main_table.base_discount_amount),0)'),
            'base_currency_code' => new Zend_Db_Expr("IFNULL(`order`.`base_currency_code`,'" . $currencyCode . "')"),
            'order_currency_code' => new Zend_Db_Expr("IFNULL(`order`.`order_currency_code`,'" . $currencyCode . "')"),
            'sum_qty_ordered' => new Zend_Db_Expr('IFNULL(SUM(main_table.qty_ordered),0)'),
            'product_sku' => 'product.sku',
            //'child_product_qty' => 'GROUP_CONCAT(CONCAT_WS(":", main_table.sku, main_table.qty_ordered) SEPARATOR ",")',
        ));
        return $this;
    }

    public function getAssignedSupplierProductSkus(){
        $supplierProductIds = array();
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $supplierProductSql = "SELECT DISTINCT `product_id` FROM ".$resource->getTableName('erp_inventory_supplier_product');
        $query = $readConnection->query($supplierProductSql);
        while ($result = $query->fetch()) {
            $supplierProductIds[] = $result['product_id'];
        }
        $skus = array();
        $products = Mage::getResourceModel('catalog/product_collection')
            ->addFieldToFilter('entity_id', array('in' => $supplierProductIds));
        foreach($products as $product){
            $skus[] = $product->getSku();
        }
        return $skus;
    }

}
