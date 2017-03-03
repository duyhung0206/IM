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
class Magestore_Inventoryreports_Model_Mysql4_Sales_Order_Collection 
    extends Mage_Sales_Model_Mysql4_Order_Collection{
    
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
    public function getSize()
    {
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

    public function addHaving($having){
        $this->getSelect()->having($having);
    }

    public function addColumns($columns){
        $this->getSelect()->columns($columns);
    }

    public function getHoursofdayReportCollection($datefrom, $dateto, $supplier, $source) {

        if ($supplier) {
            $orderIds = $this->filterWithSupplier($supplier);
            $this->addFieldToFilter('entity_id', array('in' => $orderIds));
        }
        if ($source) {
            $this->getSelect()
                ->joinLeft(
                    array('source' => $this->getTable('webpos/survey')), 'main_table.entity_id = source.order_id', array('*')
                )
                ->where("source.value='" . $source . "'")
            ;
        }
        $toTimezone = Mage::getSingleton('core/date')->getGmtOffset('hours') . ':00';
        if (Mage::getSingleton('core/date')->getGmtOffset('hours') >= 0)
            $toTimezone = '+' . $toTimezone;
        $fromTimezone = (date("Z") >= 0) ? '+' . date("Z") . ':00' : date("Z") . ':00';

        $this->getSelect()->columns(array(
            'all_order_id' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT main_table.entity_id SEPARATOR ",")'),
            'time_range' => new Zend_Db_Expr("hour(CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone'))"),
            'count_entity_id' => new Zend_Db_Expr('COUNT(DISTINCT main_table.entity_id)'),
            'sum_base_tax_amount' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_tax_amount),0)'),
            'sum_tax_amount' => new Zend_Db_Expr('IFNULL(SUM(main_table.tax_amount),0)'),
            'sum_subtotal' => new Zend_Db_Expr('IFNULL(SUM(main_table.subtotal),0)'),
            'sum_base_subtotal' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_subtotal),0)'),
            'grand_total_per_hour' => new Zend_Db_Expr('IFNULL(SUM(main_table.grand_total),0)'
                . ' / DATEDIFF(\'' . $dateto . '\', \'' . $datefrom . '\' )'),
            'base_grand_total_per_hour' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_grand_total),0)'
                . ' / DATEDIFF(\'' . $dateto . '\', \'' . $datefrom . '\' )'),
            'sum_grand_total' => new Zend_Db_Expr('IFNULL(SUM(main_table.grand_total),0)'),
            'sum_base_grand_total' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_grand_total),0)'),
            'sum_qty_ordered' => new Zend_Db_Expr('IFNULL(SUM(main_table.total_qty_ordered),0)'),
            'qty_ordered_per_hour' => new Zend_Db_Expr('ROUND(IFNULL(SUM(main_table.total_qty_ordered),0)'
                . ' / DATEDIFF(\'' . $dateto . '\', \'' . $datefrom . '\' ), 2)'),
            'total_order_per_hour' => new Zend_Db_Expr('ROUND(COUNT(DISTINCT main_table.entity_id)'
                . ' / DATEDIFF(\'' . $dateto . '\', \'' . $datefrom . '\' ), 2)'),
        ));
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone')  >= '$datefrom'");
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone') <=  '$dateto'" );
        $this->groupBy(new Zend_Db_Expr("hour(CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone'))"));
        //$collection->setOrder(("hour(CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone'))"), 'ASC');
        $this->setOrder(("hour(CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone'))"), 'ASC');
        
        return $this;
    }

    public function getDaysofweekReportCollection($datefrom, $dateto, $supplier, $source) {

        $toTimezone = Mage::getSingleton('core/date')->getGmtOffset('hours') . ':00';
        if (Mage::getSingleton('core/date')->getGmtOffset('hours') >= 0)
            $toTimezone = '+' . $toTimezone;
        $fromTimezone = (date("Z") >= 0) ? '+' . date("Z") . ':00' : date("Z") . ':00';
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone')  >= '$datefrom'");
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone') <=  '$dateto'" );

        if ($supplier) {
            $orderIds = $this->filterWithSupplier($supplier);
            $this->addFieldToFilter('entity_id', array('in' => $orderIds));
        }
        if ($source) {
            $this->getSelect()
                ->joinLeft(
                    array('source' => $this->getTable('webpos/survey')), 'main_table.entity_id = source.order_id', array('*')
                )
                ->where("source.value='" . $source . "'")
            ;
        }
        $totalWeek = 'TIMESTAMPDIFF(WEEK, \'' . $datefrom . '\', \'' . $dateto . '\' )';
        $this->groupBy(new Zend_Db_Expr("dayofweek(CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone'))"));
        $this->setOrder(("dayofweek(CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone'))"), 'ASC');
        $this->getSelect()->columns(array(
            'all_order_id' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT main_table.entity_id SEPARATOR ",")'),
            'time_range' => new Zend_Db_Expr("dayofweek(CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone'))"),
            'count_entity_id' => new Zend_Db_Expr('COUNT(DISTINCT main_table.entity_id)'),
            'sum_base_tax_amount' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_tax_amount),0)'),
            'sum_tax_amount' => new Zend_Db_Expr('IFNULL(SUM(main_table.tax_amount),0)'),
            'sum_subtotal' => new Zend_Db_Expr('IFNULL(SUM(main_table.subtotal),0)'),
            'sum_base_subtotal' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_subtotal),0)'),
            'sum_grand_total' => new Zend_Db_Expr('IFNULL(SUM(main_table.grand_total),0)'),
            'sum_base_grand_total' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_grand_total),0)'),
            'sum_qty_ordered' => new Zend_Db_Expr('IFNULL(SUM(main_table.total_qty_ordered),0)'),
            'base_grand_total_per_day' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_grand_total),0)'
                . " / IF($totalWeek > 0, $totalWeek, 1)"),
            'qty_ordered_per_day' => new Zend_Db_Expr('ROUND(IFNULL(SUM(main_table.total_qty_ordered),0)'
                . " / IF($totalWeek > 0, $totalWeek, 1), 2)"),
            'total_order_per_day' => new Zend_Db_Expr('ROUND(COUNT(DISTINCT main_table.entity_id)'
                . " / IF($totalWeek > 0, $totalWeek, 1), 2)"),
        ));


        return $this;
    }

    public function getDaysofmonthReportCollection($datefrom, $dateto, $supplier, $group, $source) {
        $toTimezone = Mage::getSingleton('core/date')->getGmtOffset('hours') . ':00';
        if (Mage::getSingleton('core/date')->getGmtOffset('hours') >= 0)
            $toTimezone = '+' . $toTimezone;
        $fromTimezone = (date("Z") >= 0) ? '+' . date("Z") . ':00' : date("Z") . ':00';
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone')  >= '$datefrom'");
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone') <=  '$dateto'" );


        if ($supplier) {
            $orderIds = $this->filterWithSupplier($supplier);
            $this->addFieldToFilter('entity_id', array('in' => $orderIds));
        }
        if ($source) {
            $this->getSelect()
                ->joinLeft(
                    array('source' => $this->getTable('webpos/survey')), 'main_table.entity_id = source.order_id', array('*')
                )
                ->where("source.value='" . $source . "'")
            ;
        }
        if ($group) {
            $totalMonth = 'TIMESTAMPDIFF(MONTH, \'' . $datefrom . '\', \'' . $dateto . '\' )';
            $this->groupBy(new Zend_Db_Expr("dayofmonth(CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone'))"));
            $this->setOrder(("dayofmonth(CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone'))"), 'ASC');
            $this->getSelect()->columns(array(
                'all_order_id' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT main_table.entity_id SEPARATOR ",")'),
                'time_range' => new Zend_Db_Expr("dayofmonth(CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone'))"),
                'count_entity_id' => new Zend_Db_Expr('COUNT(DISTINCT main_table.entity_id)'),
                'sum_base_tax_amount' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_tax_amount),0)'),
                'sum_tax_amount' => new Zend_Db_Expr('IFNULL(SUM(main_table.tax_amount),0)'),
                'sum_subtotal' => new Zend_Db_Expr('IFNULL(SUM(main_table.subtotal),0)'),
                'sum_base_subtotal' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_subtotal),0)'),
                'sum_grand_total' => new Zend_Db_Expr('IFNULL(SUM(main_table.grand_total),0)'),
                'sum_base_grand_total' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_grand_total),0)'),
                'sum_qty_ordered' => new Zend_Db_Expr('IFNULL(SUM(main_table.total_qty_ordered),0)'),
                'total_order_per_day' => new Zend_Db_Expr('ROUND(COUNT(DISTINCT main_table.entity_id)'
                    . " / IF($totalMonth > 0, $totalMonth, 1), 2)"),
                'qty_ordered_per_day' => new Zend_Db_Expr('ROUND(IFNULL(SUM(main_table.total_qty_ordered),0)'
                    . " / IF($totalMonth > 0, $totalMonth, 1), 2)"),
                'base_grand_total_per_day' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_grand_total),0)'
                    . " / IF($totalMonth > 0, $totalMonth, 1)"),
            ));
        } else {
            $this->groupBy(new Zend_Db_Expr("DATE(CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone'))"));
            $this->setOrder(("DATE(CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone'))"), 'ASC');
            $this->getSelect()->columns(array(
                'all_order_id' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT main_table.entity_id SEPARATOR ",")'),
                'time_range' => new Zend_Db_Expr("DATE(CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone'))"),
                'count_entity_id' => new Zend_Db_Expr('COUNT(DISTINCT main_table.entity_id)'),
                'sum_base_tax_amount' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_tax_amount),0)'),
                'sum_tax_amount' => new Zend_Db_Expr('IFNULL(SUM(main_table.tax_amount),0)'),
                'sum_subtotal' => new Zend_Db_Expr('IFNULL(SUM(main_table.subtotal),0)'),
                'sum_base_subtotal' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_subtotal),0)'),
                'sum_grand_total' => new Zend_Db_Expr('IFNULL(SUM(main_table.grand_total),0)'),
                'sum_base_grand_total' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_grand_total),0)'),
                'sum_qty_ordered' => new Zend_Db_Expr('IFNULL(SUM(main_table.total_qty_ordered),0)')
            ));

        }

        return $this;
    }

    public function getInvoiceReportCollection($datefrom, $dateto, $supplier, $source) {
        $toTimezone = Mage::getSingleton('core/date')->getGmtOffset('hours') . ':00';
        if (Mage::getSingleton('core/date')->getGmtOffset('hours') >= 0)
            $toTimezone = '+' . $toTimezone;
        $fromTimezone = (date("Z") >= 0) ? '+' . date("Z") . ':00' : date("Z") . ':00';
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone')  >= '$datefrom'");
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone') <=  '$dateto'" );
        $this->addFieldToFilter('base_subtotal_invoiced', array('gt' => 0));

        if ($supplier) {
            $orderIds = $this->filterWithSupplier($supplier);
            $this->addFieldToFilter('entity_id', array('in' => $orderIds));
        }
        if ($source) {
            $this->getSelect()
                ->joinLeft(
                    array('source' => $this->getTable('webpos/survey')), 'main_table.entity_id = source.order_id', array('*')
                )
                ->where("source.value='" . $source . "'")
            ;
        }
        $this->getSelect()->joinLeft(array(
            'orderinvoice' => $this->getTable('sales/invoice')), 'main_table.entity_id = orderinvoice.order_id', array('count_invoice_id' => 'IFNULL(COUNT( DISTINCT orderinvoice.entity_id),0)')
        );

        $this->getSelect()->joinLeft(array(
            'orderitem' => $this->getTable('sales/order_item')), 'main_table.entity_id = orderitem.order_id AND orderitem.parent_item_id IS NULL', array('orderitem.item_id')
        );

        $this->getSelect()->joinLeft(array(
            'invoiceitem' => $this->getTable('sales/invoice_item')), 'orderinvoice.entity_id = invoiceitem.parent_id AND orderitem.item_id = invoiceitem.order_item_id', array('sum_invoice_item_qty' => 'IFNULL(SUM(invoiceitem.qty),0)')
        );

        $this->groupBy('main_table.entity_id');

        $this->getSelect()->columns(array(
            'all_order_id' => 'GROUP_CONCAT(DISTINCT main_table.entity_id SEPARATOR ",")',
            'order_id' => 'main_table.increment_id',
            'count_entity_id' => 'COUNT(DISTINCT main_table.entity_id)',
            'sum_base_tax_amount_invoiced' => 'IFNULL(SUM(main_table.base_tax_invoiced),0)',
            'sum_tax_amount_invoiced' => 'IFNULL(SUM(main_table.tax_invoiced),0)',
            'sum_subtotal_invoiced' => 'IFNULL(SUM(main_table.subtotal_invoiced),0)',
            'sum_base_subtotal_invoiced' => 'IFNULL(SUM(main_table.base_subtotal_invoiced),0)',
            'sum_grand_total_invoiced' => 'IFNULL(SUM(main_table.total_invoiced),0)',
            'sum_base_grand_total_invoiced' => 'IFNULL(SUM(main_table.base_total_invoiced),0)',
            'sum_base_tax_amount' => 'IFNULL(SUM(main_table.base_tax_amount),0)',
            'sum_tax_amount' => 'IFNULL(SUM(main_table.tax_amount),0)',
            'sum_subtotal' => 'IFNULL(SUM(main_table.subtotal),0)',
            'sum_base_subtotal' => 'IFNULL(SUM(main_table.base_subtotal),0)',
            'sum_grand_total' => 'IFNULL(SUM(main_table.grand_total),0)',
            'sum_base_grand_total' => 'IFNULL(SUM(main_table.base_grand_total),0)',
            'sum_qty_ordered' => 'IFNULL(SUM(main_table.total_qty_ordered),0)'
        ));

        return $this;
    }

    public function getCreditmemoReportCollection($datefrom, $dateto, $supplier, $source) {
        $toTimezone = Mage::getSingleton('core/date')->getGmtOffset('hours') . ':00';
        if (Mage::getSingleton('core/date')->getGmtOffset('hours') >= 0)
            $toTimezone = '+' . $toTimezone;
        $fromTimezone = (date("Z") >= 0) ? '+' . date("Z") . ':00' : date("Z") . ':00';
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone')  >= '$datefrom'");
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone') <=  '$dateto'" );
        $this->addFieldToFilter('base_subtotal_refunded', array('gt' => 0));
        if ($supplier) {
            $orderIds = $this->filterWithSupplier($supplier);
            $this->addFieldToFilter('entity_id', array('in' => $orderIds));
        }
        if ($source) {
            $this->getSelect()
                ->joinLeft(
                    array('source' => $this->getTable('webpos/survey')), 'main_table.entity_id = source.order_id', array('*')
                )
                ->where("source.value='" . $source . "'")
            ;
        }
        $this->getSelect()->joinLeft(array(
            'ordercreditmemo' => $this->getTable('sales/creditmemo')), 'main_table.entity_id = ordercreditmemo.order_id', array('count_creditmemo_id' => 'IFNULL(COUNT( DISTINCT ordercreditmemo.entity_id),0)')
        );

        $this->getSelect()->joinLeft(array(
            'orderitem' => $this->getTable('sales/order_item')), 'main_table.entity_id = orderitem.order_id AND orderitem.parent_item_id IS NULL', array('orderitem.item_id')
        );

        $this->getSelect()->joinLeft(array(
            'creditmemoitem' => $this->getTable('sales/creditmemo_item')), 'ordercreditmemo.entity_id = creditmemoitem.parent_id AND orderitem.item_id = creditmemoitem.order_item_id', array('sum_creditmemo_item_qty' => 'IFNULL(SUM(creditmemoitem.qty),0)')
        );

        $this->groupBy('main_table.entity_id');

        $this->getSelect()->columns(array(
            'all_order_id' => 'GROUP_CONCAT(DISTINCT main_table.entity_id SEPARATOR ",")',
            'order_id' => 'main_table.increment_id',
            'count_entity_id' => 'COUNT(DISTINCT main_table.entity_id)',
            'sum_base_tax_amount_refunded' => 'IFNULL(SUM(main_table.base_tax_refunded),0)',
            'sum_tax_amount_refunded' => 'IFNULL(SUM(main_table.tax_refunded),0)',
            'sum_subtotal_refunded' => 'IFNULL(SUM(main_table.subtotal_refunded),0)',
            'sum_base_subtotal_refunded' => 'IFNULL(SUM(main_table.base_subtotal_refunded),0)',
            'sum_grand_total_refunded' => 'IFNULL(SUM(main_table.total_refunded),0)',
            'sum_base_grand_total_refunded' => 'IFNULL(SUM(main_table.base_total_refunded),0)',
            'sum_base_tax_amount' => 'IFNULL(SUM(main_table.base_tax_amount),0)',
            'sum_tax_amount' => 'IFNULL(SUM(main_table.tax_amount),0)',
            'sum_subtotal' => 'IFNULL(SUM(main_table.subtotal),0)',
            'sum_base_subtotal' => 'IFNULL(SUM(main_table.base_subtotal),0)',
            'sum_grand_total' => 'IFNULL(SUM(main_table.grand_total),0)',
            'sum_base_grand_total' => 'IFNULL(SUM(main_table.base_grand_total),0)',
            'sum_qty_ordered' => 'IFNULL(SUM(main_table.total_qty_ordered),0)'
        ));
        return $this;
    }

    public function getSalesWarehouseReportCollection1($datefrom, $dateto, $source) {

        $toTimezone = Mage::getSingleton('core/date')->getGmtOffset('hours') . ':00';
        if (Mage::getSingleton('core/date')->getGmtOffset('hours') >= 0)
            $toTimezone = '+' . $toTimezone;
        $fromTimezone = (date("Z") >= 0) ? '+' . date("Z") . ':00' : date("Z") . ':00';
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone')  >= '$datefrom'");
        $this->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone') <=  '$dateto'" );
        $this->getSelect()->join(
            array('orderItem' => $this->getTable('sales/order_item')), 'main_table.entity_id= orderItem.order_id', array('base_row_total_incl_tax', 'row_total_incl_tax', 'qty_ordered')
        );
        $this->getSelect()->joinLeft(
            array('productSuper' => $this->getTable('catalog/product_super_link')), 'orderItem.product_id= productSuper.parent_id', array('product_id')
        );
        $this->getSelect()->joinLeft(
            array('warehouseShip' => $this->getTable('inventoryplus/warehouse_shipment')),
            'IFNULL(productSuper.product_id, orderItem.product_id) = warehouseShip.product_id '
            . ' AND orderItem.order_id = warehouseShip.order_id',
            array('qty_shipped', 'qty_refunded', 'warehouse_name')
        );

        $this->getSelect()->where('`warehouseShip`.`warehouse_id` is NULL');
        //$collection->getSelect()->group("warehouseShip.warehouse_id");
        //$collection->setOrder(("IFNULL(SUM(warehouseShip.subtotal_shipped),0)"), 'DESC');
        $currencyCode = Mage::app()->getStore()->getBaseCurrency()->getCode();
        $this->getSelect()->columns(array(
            'all_order_id' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT `main_table`.`entity_id` SEPARATOR ",")'),
            'count_entity_id' => new Zend_Db_Expr('COUNT(DISTINCT main_table.entity_id)'),
            'sum_base_tax_amount' => '0',
            'sum_tax_amount' => '0',
            'sum_grand_total' => new Zend_Db_Expr('IFNULL( SUM( orderItem.row_total_incl_tax * (warehouseShip.qty_shipped - warehouseShip.qty_refunded) / orderItem.qty_ordered ),'
                . ' SUM(orderItem.row_total_incl_tax))'),
            'sum_base_grand_total' => new Zend_Db_Expr('IFNULL( SUM( orderItem.base_row_total_incl_tax * (warehouseShip.qty_shipped - warehouseShip.qty_refunded) / orderItem.qty_ordered ),'
                . ' SUM(orderItem.base_row_total_incl_tax))'),
            'base_currency_code' => new Zend_Db_Expr("IFNULL(`main_table`.`base_currency_code`,'" . $currencyCode . "')"),
            'order_currency_code' => new Zend_Db_Expr("IFNULL(`main_table`.`order_currency_code`,'" . $currencyCode . "')"),
            'sum_qty_ordered' => new Zend_Db_Expr('SUM(IFNULL(warehouseShip.qty_shipped, orderItem.qty_ordered))'),
            'warehouse_name' => new Zend_Db_Expr('IFNULL(warehouseShip.warehouse_name,\'' . $this->__('Unassigned Warehouse') . '\')')
        ));
        $this->getSelect()->distinct();

        return $this;
    }

    

    

}
