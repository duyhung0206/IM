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
class Magestore_Inventoryreports_Helper_Order extends Mage_Core_Helper_Abstract {

    public function getTimezoneOffset($timezone) {
        //get offset for site timezone
        $time = new DateTime(date('Y-m-d H:i:s'), new DateTimeZone($timezone));
        $timezoneOffset = $time->format('P');
        return $timezoneOffset;
    }

    /**
     * Get requested time range
     *
     * @param array $requestData
     * @return array
     */
    public function getTimeRange($requestData) {
        return Mage::helper('inventoryreports')->getTimeRange($requestData);
    }

    public function filterWithSupplier($supplier) {
        $arrayCollection = array();
        //filter with supplier
        $orderItems = Mage::getModel('sales/order_item')->getCollection()
            ->addFieldToFilter('parent_item_id', array('null' => true))
            ->addFieldToFilter('product_type', array('nin' => array("configurable", "bundle", "grouped")))
        ;
        $orderItems->getSelect()
            ->join(array(
                'supplierproduct' => $orderItems->getTable('inventorypurchasing/supplier_product')), 'main_table.product_id = supplierproduct.product_id and supplierproduct.supplier_id=' . $supplier, array("*")
            );
        $orderItems->groupBy('order_id');
        $orderIdsSupplier = array();
        foreach ($orderItems as $orderItem) {
            $orderIdsSupplier[$orderItem->getOrderId()] = $orderItem->getOrderId();
        }
        return $orderIdsSupplier;
    }

    /**
     * Get order collection resource
     *
     * @return \Magestore_Inventoryreports_Model_Mysql4_Sales_Order_Collection
     */
    public function getOrderCollection() {
        return Mage::getResourceModel('inventoryreports/sales_order_collection');
    }

    /**
     * Get order item collection resource
     *
     * @return \Magestore_Inventoryreports_Model_Mysql4_Sales_Order_Item_Collection
     */
    public function getOrderItemCollection() {
        return Mage::getResourceModel('inventoryreports/sales_order_item_collection');
    }


    /**
     * Get collection of sales reports
     *
     * @param array $requestData
     * @return array
     */
    public function getOrderReportCollection($requestData) {
        $coreResource = Mage::getSingleton('core/resource');
        $coreResource->getConnection('core_write')->query('SET SESSION group_concat_max_len = 1000000;');
        $source = isset($requestData['source_select']) ? $requestData['source_select'] : null;
        $supplier = isset($requestData['supplier_select']) ? $requestData['supplier_select'] : null;
        $report_type = isset($requestData['report_radio_select']) ? $requestData['report_radio_select'] : null;
        $arrayCollection = array();
        $timeRange = $this->getTimeRange($requestData);
        $datefrom = $timeRange['from'];
        $dateto = $timeRange['to'];
        /* Prepare Collection */
        //switch report type
        switch ($report_type) {
            case 'hours_of_day':
                $arrayCollection = $this->getHoursofdayReportCollection($datefrom, $dateto, $supplier, $source);
                break;
            case 'days_of_week':
                $arrayCollection = $this->getDaysofweekReportCollection($datefrom, $dateto, $supplier, $source);
                break;
            case 'days_of_month':
                $arrayCollection = $this->getDaysofmonthReportCollection($datefrom, $dateto, $supplier, true, $source);
                break;
            case 'sales_days':
                $arrayCollection = $this->getDaysofmonthReportCollection($datefrom, $dateto, $supplier, false, $source);
                break;
            case 'sales_warehouse':
                $arrayCollection = $this->getSalesWarehouseReportCollection($datefrom, $dateto, $source);
                break;
            case 'sales_supplier':
                $arrayCollection = $this->getSalesSupplierReportCollection($datefrom, $dateto, $source);
                break;
            case 'sales_sku':
                $arrayCollection = $this->getSalesSKUReportCollection($datefrom, $dateto, $source);
                break;
            //report by order attributes
            case 'shipping_method':
            case 'payment_method':
            case 'status':
                $collection = Mage::getResourceModel('sales/order_collection');
                $toTimezone = Mage::getSingleton('core/date')->getGmtOffset('hours') . ':00';
                if (Mage::getSingleton('core/date')->getGmtOffset('hours') >= 0)
                    $toTimezone = '+' . $toTimezone;
                $fromTimezone = (date("Z") >= 0) ? '+' . date("Z") . ':00' : date("Z") . ':00';


                //check if colllection have no record
                if (count($collection) == 0) {
                    return $collection;
                }
                $attribute = $report_type;
                $cData = clone $collection;
                $cData = $cData->setPageSize(1)->getFirstItem()->getData();
                if (!isset($cData[$attribute])) {
                    //sales by payment method


                    $collection = $this->prepareOrderAttributeCollection($attribute, $datefrom, $dateto, $supplier, $source);
                    $arrayCollection['collection'] = $collection;
                    $arrayCollection['filter'] = array(
                        'default' => 'main_table',
                        'count_item_id' => 'orderitem',
                        'count_entity_id' => 'order',
                    );
                } else {
                    $collection = $this->getOrderCollection();
                    $collection->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone')  >= '$datefrom'");
                    $collection->getSelect()->where("CONVERT_TZ(main_table.created_at,'$fromTimezone','$toTimezone') <=  '$dateto'" );
                    if ($supplier) {
                        $orderIds = $this->filterWithSupplier($supplier);
                        $collection->addFieldToFilter('entity_id', array('in' => $orderIds));
                    }
                    if ($source) {
                        $collection->getSelect()
                            ->joinLeft(
                                array('source' => $collection->getTable('webpos/survey')), 'main_table.entity_id = source.order_id', array('*')
                            )
                            ->where("source.value='" . $source . "'")
                        ;
                    }
                    $collection->getSelect()->columns(array(
                        'all_order_id' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT main_table.entity_id SEPARATOR ",")'),
                        'att_' . $attribute => $attribute,
                        'att_shipping_method' => new Zend_Db_Expr('IFNULL(main_table.shipping_description,"No Shipping")'),
                        'count_entity_id' => new Zend_Db_Expr('COUNT(DISTINCT main_table.entity_id)'),
                        'sum_base_tax_amount' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_tax_amount),0)'),
                        'sum_tax_amount' => new Zend_Db_Expr('IFNULL(SUM(main_table.tax_amount),0)'),
                        'sum_subtotal' => new Zend_Db_Expr('IFNULL(SUM(main_table.subtotal),0)'),
                        'sum_base_subtotal' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_subtotal),0)'),
                        'sum_grand_total' => new Zend_Db_Expr('IFNULL(SUM(main_table.grand_total),0)'),
                        'sum_base_grand_total' => new Zend_Db_Expr('IFNULL(SUM(main_table.base_grand_total),0)'),
                        'sum_qty_ordered' => new Zend_Db_Expr('IFNULL(SUM(main_table.total_qty_ordered),0)')
                    ));
                    $collection->groupBy('main_table.' . $attribute);
                    $arrayCollection['collection'] = $collection;
                    $arrayCollection['filter'] = array(
                        'default' => 'main_table',
                        'count_item_id' => 'orderitem',
                    );
                }
        }
        //Filter by order status
        if (isset($requestData['order_status'])) {
            $this->_filterByOrderStatus($arrayCollection, $requestData['order_status']);
        }

        /* end Prepare Collection */
        return $arrayCollection;
    }

    public function getHoursofdayReportCollection($datefrom, $dateto, $supplier, $source) {
        $arrayCollection = array();
        $collection = $this->getOrderCollection();
        $collection->getHoursofdayReportCollection($datefrom, $dateto, $supplier, $source);
        $arrayCollection['collection'] = $collection;
        $arrayCollection['filter'] = array(
            'default' => 'main_table',
            'count_item_id' => 'orderitem',
        );
        return $arrayCollection;
    }

    public function getDaysofweekReportCollection($datefrom, $dateto, $supplier, $source) {
        $arrayCollection = array();
        //get timezone
        $collection = $this->getOrderCollection();
        $collection->getDaysofweekReportCollection($datefrom, $dateto, $supplier, $source);
        $arrayCollection['collection'] = $collection;
        $arrayCollection['filter'] = array(
            'default' => 'main_table',
            'count_item_id' => 'orderitem',
        );
        return $arrayCollection;
    }

    public function getDaysofmonthReportCollection($datefrom, $dateto, $supplier, $group, $source) {
        $arrayCollection = array();
        $collection = $this->getOrderCollection();
        $collection->getDaysofmonthReportCollection($datefrom, $dateto, $supplier, $group, $source);
        $arrayCollection['collection'] = $collection;

        $arrayCollection['filter'] = array(
            'default' => 'main_table',
            'count_item_id' => 'orderitem',
        );
        return $arrayCollection;
    }

    public function getInvoiceReportCollection($datefrom, $dateto, $supplier, $source) {
        $collection = $this->getOrderCollection();
        $collection->getInvoiceReportCollection($datefrom, $dateto, $supplier, $source);
        return $collection;
    }

    public function getCreditmemoReportCollection($datefrom, $dateto, $supplier, $source) {
        $collection = $this->getOrderCollection();
        $collection->getCreditmemoReportCollection($datefrom, $dateto, $supplier, $source);
        return $collection;
    }

    /**
     * Get sales by warehouses
     *
     * @param string $datefrom
     * @param string $dateto
     * @param string $source
     */
    public function getSalesWarehouseReportCollection1($datefrom, $dateto, $source) {
        $collection = $this->getOrderCollection();
        $collection->getSalesWarehouseReportCollection1($datefrom, $dateto, $source);
        $arrayCollection['collection'] = $collection;
        $arrayCollection['filter'] = array(
            'default' => 'main_table',
        );

        return $arrayCollection;
    }

    public function getUnassignedWarehouseSales($requestData) {
        if(Mage::registry('unassigned_warehouse_sales')){
            return Mage::registry('unassigned_warehouse_sales');
        }
        $collection = $this->getOrderItemCollection();
        $collection->getUnassignedWarehouseSalesCollection($requestData);
        if (isset($requestData['order_status'])) {
            $collectionData = array('collection' => $collection);
            $this->_filterByOrderStatus($collectionData, $requestData['order_status']);
        }
        $item = $collection->getPageSize(1)->getFirstItem();
        Mage::register('unassigned_warehouse_sales', $item);
        return $item;
    }

    public function getSalesWarehouseReportCollection($datefrom, $dateto, $source) {
        Mage::log($source);
        $collection = Mage::getResourceModel('inventoryreports/warehouse_shipment_collection');
        $collection->getSalesWarehouseReportCollection($datefrom, $dateto, $source);
        $arrayCollection['collection'] = $collection;
        $arrayCollection['filter'] = array(
            'default' => 'main_table',
        );

        return $arrayCollection;
    }

    /**
     * Get sales by supplier
     *
     * @param string $datefrom
     * @param string $dateto
     * @param string $source
     */
    public function getSalesSupplierReportCollection($datefrom, $dateto, $source) {
        Mage::log($source);
        $collection = Mage::getResourceModel('inventoryreports/supplier_collection');
        $collection->getSalesSupplierReportCollection($datefrom, $dateto, $source);
        $arrayCollection['collection'] = $collection;
        $arrayCollection['filter'] = array(
            'default' => 'main_table',
        );
        return $arrayCollection;
    }

    /**
     * Get unassigned supplier sales data
     *
     * @param array $requestData
     * @return Varien_Object
     */
    public function getUnassignedSupplierSales($requestData) {
        if(Mage::registry('unassigned_supplier_sales')){
            return Mage::registry('unassigned_supplier_sales');
        }
        $collection = $this->getOrderItemCollection();
        $collection->getUnassignedSupplierSales($requestData);
        //Filter by order status
        if (isset($requestData['order_status'])) {
            $collectionData = array('collection' => $collection);
            $this->_filterByOrderStatus($collectionData, $requestData['order_status']);
        }
        $item = $collection->setPageSize(1)->getFirstItem();
        Mage::register('unassigned_supplier_sales', $item);
        return $item;
    }

    /**
     * Get Assigned supplier product ids
     *
     * @return array
     */
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

    /**
     * Get sales by SKU
     *
     * @param string $datefrom
     * @param string $dateto
     * @param string $source
     */
    public function getSalesSKUReportCollection($datefrom, $dateto, $source) {
        Mage::log($source);
        $collection = $this->getOrderItemCollection();
        $collection->getSalesSKUReportCollection($datefrom, $dateto, $source);
        $arrayCollection['collection'] = $collection;
        $arrayCollection['filter'] = array(
            'default' => 'main_table',
        );

        return $arrayCollection;
    }



    public function getOrderAttributeClass($class) {
        return 'sales/order_' . $class;
    }

    /**
     * Get order attribute collection class path
     *
     * @param string $attribute
     * @return string
     */
    public function getOrderAttributeCollection($attribute) {
        return 'inventoryreports/sales_order_' . $attribute . '_collection';
    }

    /**
     * Sales by payment method
     *
     * @param string $attribute
     * @param string $dateFrom
     * @param string $dateTo
     * @param string $supplier
     * @param string $source
     * @return collection
     */
    public function prepareOrderAttributeCollection($attribute, $dateFrom, $dateTo, $supplier, $source) {
        Mage::log($supplier.''.$source);
        $elements = explode('_', $attribute, 2);
        $attribute = $elements[0];
        $field = $elements[1];
        $collection = Mage::getResourceModel($this->getOrderAttributeCollection($attribute));
        $orderField = 'order_id';
        if ($attribute == 'payment') {
            $orderField = 'parent_id';
            $collection->getSelect()->join(
                array('core_config' => $collection->getTable('core/config_data')), 
                'core_config.path LIKE ' . new Zend_Db_Expr('CONCAT("payment/",`main_table`.`method`,"/title")') .' and `core_config`.scope = "default" ',
                array('att_payment_method' => 'core_config.value')
            );
        }
        $collection->getSelect()
            ->joinLeft(array(
                'order' => $collection->getTable('sales/order')), '`main_table`.`' . $orderField . '` = `order`.`entity_id`', array("*")
            )
        ;
        $toTimezone = Mage::getSingleton('core/date')->getGmtOffset('hours') . ':00';
        if (Mage::getSingleton('core/date')->getGmtOffset('hours') >= 0)
            $toTimezone = '+' . $toTimezone;
        $fromTimezone = (date("Z") >= 0) ? '+' . date("Z") . ':00' : date("Z") . ':00';
        $collection->getSelect()->where("CONVERT_TZ(order.created_at,'$fromTimezone','$toTimezone')  >= '$dateFrom'");
        $collection->getSelect()->where("CONVERT_TZ(order.created_at,'$fromTimezone','$toTimezone') <=  '$dateTo'" );

        $collection->groupBy("main_table.$field");
        $currencyCode = Mage::app()->getStore()->getBaseCurrency()->getCode();
        $collection->getSelect()->columns(array(
            'all_order_id' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT order.entity_id SEPARATOR ",")'),
            'count_entity_id' => new Zend_Db_Expr('IFNULL(COUNT(DISTINCT `order`.`entity_id`),0)'),
            'sum_base_tax_amount' => new Zend_Db_Expr('IFNULL(SUM(`order`.`base_tax_amount`),0)'),
            'sum_tax_amount' => new Zend_Db_Expr('IFNULL(SUM(`order`.`tax_amount`),0)'),
            'sum_subtotal' => new Zend_Db_Expr('IFNULL(SUM(`order`.`subtotal`),0)'),
            'sum_base_subtotal' => new Zend_Db_Expr('IFNULL(SUM(`order`.`base_subtotal`),0)'),
            'sum_grand_total' => new Zend_Db_Expr('IFNULL(SUM(`order`.`grand_total`),0)'),
            'sum_base_grand_total' => new Zend_Db_Expr('IFNULL(SUM(`order`.`base_grand_total`),0)'),
            'base_currency_code' => new Zend_Db_Expr("IFNULL(`order`.`base_currency_code`,'" . $currencyCode . "')"),
            'order_currency_code' => new Zend_Db_Expr("IFNULL(`order`.`order_currency_code`,'" . $currencyCode . "')"),
        ));

        $collection->getSelect()->columns(array('sum_qty_ordered' => new Zend_Db_Expr('IFNULL(SUM(order.total_qty_ordered),0)')));

        return $collection;
    }

    /**
     * Add filter by order statuses
     *
     * @param array $arrayCollection
     * @param string | array $statuses
     * @return array
     */
    protected function _filterByOrderStatus(&$arrayCollection, $statuses) {
        $statuses = is_array($statuses) ? $statuses : explode(',', $statuses);
        $collection = $arrayCollection['collection'];
        $query = $collection->getSelect()->__toString();
        if (strpos($query, 'sales_flat_order` AS `order`') === false) {
            $collection->addFieldToFilter('main_table.status', array('in' => $statuses));
        } else {
            $collection->addFieldToFilter('order.status', array('in' => $statuses));
        }
        return $arrayCollection;
    }

}
