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
 * Inventoryreports Helper
 * 
 * @category    Magestore
 * @package     Magestore_Inventoryreports
 * @author      Magestore Developer
 */
class Magestore_Inventoryreports_Block_Adminhtml_Reportcontent_Reportbyorder_Statistic 
    extends Magestore_Inventoryreports_Block_Adminhtml_Reportcontent_Chart {
    
    protected function _prepareLayout() {
        $this->setTemplate('inventoryreports/content/chart/chart-content/order/statistic.phtml');
        parent::_prepareLayout();
    }
    /**
     * Get sales order collection filtered by date & statuses
     * 
     * @return Magestore_Inventoryreports_Model_Mysql4_Sales_Order_Collection
     */
    public function getOrderCollection() {
        if(!$this->hasData('order_collection')){
            $timeRange = $this->helper('inventoryreports/order')->getTimeRange($this->getRequestData());
            $statuses = $this->getRequestData('order_status');
            $statuses = is_array($statuses) ? $statuses : explode(',', $statuses);

            $collection = $this->helper('inventoryreports/order')->getOrderCollection();
            $collection->addFieldToFilter('main_table.created_at', array(
                'from' => $timeRange['from'],
                'to' => $timeRange['to'],
                'date' => true,
            ));
            $collection->addFieldToFilter('main_table.status', array('in' => $statuses));
            $columns = array(
                'count_total_order' =>  new Zend_Db_Expr('COUNT(main_table.entity_id)'),
                'count_total_item' =>  new Zend_Db_Expr('IFNULL(SUM(main_table.total_qty_ordered),0)'),
                'sum_grand_total' =>  new Zend_Db_Expr('IFNULL(SUM(main_table.grand_total),0)'),
                'sum_base_grand_total' =>  new Zend_Db_Expr('IFNULL(SUM(main_table.base_grand_total),0)'),
                'sum_subtotal' =>  new Zend_Db_Expr('IFNULL(SUM(main_table.subtotal),0)'),
                'sum_shipping_amount' =>  new Zend_Db_Expr('IFNULL(SUM(main_table.shipping_amount),0)'),
                'sum_tax_amount' =>  new Zend_Db_Expr('IFNULL(SUM(main_table.tax_amount),0)'),
                'sum_discount_amount' =>  new Zend_Db_Expr('IFNULL(SUM(main_table.discount_amount),0)')
            );
            if($this->getReport() == 'sales_sku' || $this->getReport() == 'sales_supplier') {
                $columns['sum_base_grand_total'] = new Zend_Db_Expr('IFNULL(SUM(main_table.base_grand_total) - SUM(main_table.base_shipping_amount),0)');
            }
            $collection->getSelect()->columns($columns);
            
            $this->setData('order_collection', $collection);
        }
        return $this->getData('order_collection');
    }
    
    /**
     * Get total orders
     * 
     * @return int
     */    
    public function getTotalOrder(){
        return (int) $this->getSalesStatistic('count_total_order');
    }
    
    /**
     * Get total items
     * 
     * @return int
     */        
    public function getTotalItem(){
        return (int) $this->getSalesStatistic('count_total_item');
    }
    
    /**
     * Get sum grand total
     * 
     * @return float
     */
    public function getGrandTotal() {
        return (float) $this->getSalesStatistic('sum_base_grand_total');
    }

    public function getSubTotal() {
        return (float) $this->getSalesStatistic('sum_subtotal');
    }

    public function getShippingAmount(){
        $shippingAmount = (float) $this->getSalesStatistic('sum_shipping_amount');
        return $shippingAmount;
    }
    public function getTaxAmount(){
        return (float) $this->getSalesStatistic('sum_tax_amount');
    }
    public function getDiscountAmount(){
        return (float) $this->getSalesStatistic('sum_discount_amount');
    }
    /**
     * Get sales statistics
     * 
     * @param string $field
     * @return string
     */
    public function getSalesStatistic($field){
        if(!$this->hasData('sales_statisitc')){
            $salesStatistic = $this->getOrderCollection()->setPageSize(1)->getFirstItem();
            $this->setData('sales_statistic', $salesStatistic);
        }
        return $this->getData('sales_statistic')->getData($field);
    }

}
