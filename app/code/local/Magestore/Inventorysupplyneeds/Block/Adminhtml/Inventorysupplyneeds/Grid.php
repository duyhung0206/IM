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
 * Inventorysupplyneeds Adminhtml Controller
 * 
 * @category    Magestore
 * @package     Magestore_Inventorysupplyneeds
 * @author      Magestore Developer
 */
class Magestore_Inventorysupplyneeds_Block_Adminhtml_Inventorysupplyneeds_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    protected $_helperClass = null;
    protected $_collectionGrid = null;

    public function __construct() {
        parent::__construct();
        $this->setId('inventorysupplyneedsGrid');
        $this->setDefaultSort('out_of_stock_date');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
        $helperClass = $this->getHelperClass();
        if (!$this->_collectionGrid)
            $this->_prepareCollectionInContruct($helperClass);
    }

    public function getCollectionGrid() {
        return $this->_collectionGrid;
    }

    public function sendHelperClass($send) {
        $this->_helperClass = $send; //Magestore_Inventorysupplyneeds_Block_Adminhtml_Inventorysupplyneeds
    }

    protected function getHelperClass() {
        $helperClass = $this->_helperClass;
        if (!$helperClass) {
            $filter = $this->getRequest()->getParam('top_filter');
            $helperClass = Mage::helper('inventorysupplyneeds');
            $helperClass->setTopFilter($filter);
        }
        return $helperClass;
    }

    public function _prepareCollectionInContruct($helperClass) {
        try {
            $dateto = $helperClass->getForecastTo();
            $salesFromTo = $helperClass->getSalesFromTo();
            $listItemIds = $this->getListOrderItemIds($helperClass);
            $historySelected = $helperClass->getHistorySelected();
            $getNumberDaysForecast = $helperClass->getNumberDaysForecast();
            $purchase_more_rate = $helperClass->getRatePurchaseMore();
            $rate = $purchase_more_rate / 100;
            if (!$listItemIds) {
                $collection = new Varien_Data_Collection();
            } else {
                $w_productCol = $this->getWarehouseProductCollection($helperClass);
                $orderItemCol = $this->getOrderItemCollection($listItemIds, $helperClass);                
                $coreResource = Mage::getSingleton('core/resource');
                $tempTableArr = array('supplier_temp_table', 'order_item_temp_table', 'purchase_order_product_temp');
                $this->removeTempTables($tempTableArr);
				if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorypurchasing')) {
					$s_productCol = $this->getSupplierProductCollection($helperClass);
					$poProductCol = $this->getPOProductCollection($helperClass);
					$this->createTempTable('supplier_temp_table', $s_productCol);
					$this->createTempTable('purchase_order_product_temp', $poProductCol);
				}                
                $this->createTempTable('order_item_temp_table', $orderItemCol);                
                $collection = $w_productCol;
				if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorypurchasing')) {
					$collection->getSelect()
							->join(
									array('supplier_product' => $coreResource->getTableName('supplier_temp_table')), "main_table.product_id=supplier_product.product_id", array('supplier_product.*'));
					$collection->getSelect()
                        ->joinLeft(
                                array('tmp_po_product' => $coreResource->getTableName('purchase_order_product_temp')), "main_table.product_id=tmp_po_product.product_id", array('in_purchasing'));
				}
				$collection->getSelect()
							->join(
									array('order_item' => $coreResource->getTableName('order_item_temp_table')), "main_table.product_id=order_item.product_id", array('order_item.*'));
                $collection->getSelect()->columns(array(
                    'out_of_stock_date' => new Zend_Db_Expr("DATE_ADD(CURDATE(),INTERVAL(SUM(main_table.available_qty)/order_item.avg_qty_ordered) DAY)"),
                    'supplyneeds' => new Zend_Db_Expr("GREATEST((order_item.avg_qty_ordered * {$getNumberDaysForecast} - SUM(main_table.available_qty)),0)")));
				if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorypurchasing')) {
					$collection->getSelect()->columns(array(
						'purchase_more' => new Zend_Db_Expr("CEIL(GREATEST(((order_item.avg_qty_ordered * {$getNumberDaysForecast} - SUM(main_table.available_qty))* {$rate} - IFNULL(tmp_po_product.in_purchasing,0)),0))")
					));
				}
            }
            $this->_collectionGrid = $collection;
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'inventory_management.log');
        }
    }

    protected function _prepareCollection() {
        $collection = $this->_collectionGrid;
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * prepare columns for this grid
     *
     * @return Magestore_Inventorysupplyneeds_Block_Adminhtml_Inventorysupplyneeds_Grid
     */
    protected function _prepareColumns() {
		if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorypurchasing')){
			$this->addColumn('in_products', array(
				'header_css_class' => 'a-center',
				'type' => 'checkbox',
				'name' => 'in_products',
				'values' => $this->_getSelectedProducts(),
				'align' => 'center',
				'index' => 'product_id',
				'use_index' => true,
				'disabled_values' => array()
			));
		}
        $this->addColumn('product_sku', array(
            'header' => Mage::helper('inventoryplus')->__('SKU'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'product_sku',
            'filter_condition_callback' => array($this, '_filterTextCallback')
        ));
        $this->addColumn('avg_qty_ordered', array(
            'header' => Mage::helper('inventoryplus')->__('Qty. Ordered/day'),
            'align' => 'right',
            'index' => 'avg_qty_ordered',
            'type' => 'number',
            'filter_condition_callback' => array($this, '_filterNumberCallback')
        ));
        $this->addColumn('total_qty_ordered', array(
            'header' => Mage::helper('inventoryplus')->__('Total Sold'),
            'align' => 'right',
            'index' => 'total_qty_ordered',
            'type' => 'number',
            'filter_condition_callback' => array($this, '_filterNumberCallback')
        ));
        $this->addColumn('total_available_qty', array(
            'header' => Mage::helper('inventoryplus')->__('Avail. Qty'), // Dang bi SAI
            'align' => 'right',
            'index' => 'total_available_qty',
            'type' => 'number',
            'filter_condition_callback' => array($this, '_filterNumberCallback')
        ));
        $this->addColumn('in_purchasing', array(
            'header' => Mage::helper('inventoryplus')->__('Qty On Order'),
            'align' => 'right',
            'index' => 'in_purchasing',
            'type' => 'number',
        ));
        $this->addColumn('out_of_stock_date', array(
            'header' => Mage::helper('inventoryplus')->__('Out-of-stock Date'),
            'align' => 'left',
            'index' => 'out_of_stock_date',
            'type' => 'date',
            'filter_condition_callback' => array($this, '_filterDateCallback')
        ));
        $this->addColumn('supplyneeds', array(
            'header' => Mage::helper('inventoryplus')->__('Supply Needs'),
            'align' => 'right',
            'index' => 'supplyneeds',
            'type' => 'number',
            'filter_condition_callback' => array($this, '_filterNumberCallback')
        ));
        if (!$this->_isExport && Mage::helper('core')->isModuleEnabled('Magestore_Inventorypurchasing')) {
            $this->addColumn('purchase_more', array(
                'header' => Mage::helper('inventoryplus')->__('Purchase Qty'),
                'align' => 'right',
                'width' => '80px',
                'index' => 'purchase_more',
                'type' => 'input',
                'editable' => true,
                'sortable' => false,
                'filter' => false
            ));
        }
        if (!$this->_isExport && Mage::helper('core')->isModuleEnabled('Magestore_Inventoryreports')) {
            $this->addColumn('action', array(
                'header' => Mage::helper('sales')->__('Action'),
                'width' => '50px',
                'align' => 'center',
                'type' => 'action',
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'is_system' => true,
                'renderer' => 'inventorysupplyneeds/adminhtml_inventorysupplyneeds_renderer_action'
            ));
        }
        $this->addExportType('*/*/exportCsv', Mage::helper('inventorysupplyneeds')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('inventorysupplyneeds')->__('XML'));
        return parent::_prepareColumns();
    }

    public function getGridUrl() {
        if ($filter = $this->getRequest()->getParam('top_filter'))
            return $this->getUrl('*/*/grid', array('_current' => true, 'top_filter' => $filter));
        return $this->getUrl('*/*/grid', array('_current' => true));
    }

    public function getRowUrl($row) {
        return false;
    }

    protected function getListOrderItemIds($helperClass) {
        $coreResource = Mage::getSingleton('core/resource');
        $coreResource->getConnection('core_write')->query('SET SESSION group_concat_max_len = 1000000;');
        $salesFromTo = $helperClass->getSalesFromTo();
        $warehouseSelected = $helperClass->getWarehouseSelected();
        $warehousesEnable = Mage::helper('inventoryplus/warehouse')->getAllWarehouseNameEnable();
        if(count($warehouseSelected)==count($warehousesEnable)){
            $orderItems = Mage::getModel('sales/order_item')->getCollection();
            $conditionOne = "created_at > '{$salesFromTo['from']}' AND created_at < '{$salesFromTo['to']}' ";
            $orderItems->getSelect()->where($conditionOne);
            $orderItems->getSelect()->columns(array(
                    'all_item_id' => 'GROUP_CONCAT(DISTINCT item_id SEPARATOR ",")'));
            $itemIds = $orderItems->setPageSize(1)->setCurPage(1)->getFirstItem()->getAllItemId();
        }else{
            $warehouseSelectedStr = implode(',', $warehouseSelected);
            $conditionOne = "order_item.created_at > '{$salesFromTo['from']}' AND order_item.created_at < '{$salesFromTo['to']}' AND main_table.warehouse_id IN ({$warehouseSelectedStr})";
            $warehouseOrder = Mage::getModel('inventoryplus/warehouse_order')->getCollection();
            $warehouseOrder->getSelect()
                            ->joinLeft(
                                            array('order_item' => $warehouseOrder->getTable('sales/order_item')), "main_table.item_id=order_item.item_id", array('item_id'));
            $warehouseOrder->getSelect()->where($conditionOne);
            $warehouseOrder->getSelect()->columns(array(
                    'all_item_id' => 'GROUP_CONCAT(DISTINCT main_table.item_id SEPARATOR ",")'));
            $itemIds = $warehouseOrder->setPageSize(1)->setCurPage(1)->getFirstItem()->getAllItemId();
        }
        return $itemIds;
    }

    protected function getWarehouseProductCollection($helperClass) {
        $supplyneeds = Mage::getResourceModel('inventorysupplyneeds/inventorysupplyneeds');
        $collection = $supplyneeds->gridGetWarehouseProductCollection($helperClass);
        return $collection;
    }

    protected function getSupplierProductCollection($helperClass) {
        $supplyneeds = Mage::getResourceModel('inventorysupplyneeds/inventorysupplyneeds');
        $collection = $supplyneeds->gridGetSupplierProductCollection($helperClass);           
        return $collection;
    }

    protected function getNumberHoursFromTwoDate($from, $to) {
        $hours = round((strtotime($to) - strtotime($from)) / (60 * 60));
        return $hours;
    }

    protected function getOrderItemCollection($listItemIds, $helperClass) {
        $supplyneeds = Mage::getResourceModel('inventorysupplyneeds/inventorysupplyneeds');
        $collection = $supplyneeds->gridGetOrderItemCollection($listItemIds, $helperClass);
        return $collection;
    }

    protected function getPOProductCollection($helperClass) {
        $supplyneeds = Mage::getResourceModel('inventorysupplyneeds/inventorysupplyneeds');
        $collection = $supplyneeds->gridGetPOProductCollection($helperClass);
        return $collection;
    }

    protected function removeTempTables($tempTableArr) {
        $coreResource = Mage::getSingleton('core/resource');
        $sql = "";
        foreach ($tempTableArr as $tempTable) {
            $sql .= "DROP TABLE  IF EXISTS " . $coreResource->getTableName($tempTable) . ";";
        }
        $coreResource->getConnection('core_write')->query($sql);
    }

    protected function createTempTable($tempTable, $collection) {
        $coreResource = Mage::getSingleton('core/resource');
        $_temp_sql = "CREATE TEMPORARY TABLE " . $coreResource->getTableName($tempTable) . " ("; // CREATE TEMPORARY TABLE
        $_temp_sql .= $collection->getSelect()->__toString() . ");";
        $coreResource->getConnection('core_write')->query($_temp_sql);
    }

    /**
     * Filter text field
     * 
     * @param type $collection
     * @param type $column
     * @return collection
     */
    protected function _filterTextCallback($collection, $column) {
        $filter = $column->getFilter()->getValue();
        $field = $this->_getRealFieldFromAlias($column->getIndex());
        $collection->getSelect()->where($field . ' like \'%' . $filter . '%\'');
        return $collection;
    }

    /**
     * Filter number field
     * 
     * @param type $collection
     * @param type $column
     * @return collection
     */
    protected function _filterNumberCallback($collection, $column) {
        $filter = $column->getFilter()->getValue();
        $field = $this->_getRealFieldFromAlias($column->getIndex());
        $supplyneeds = Mage::getResourceModel('inventorysupplyneeds/inventorysupplyneeds');
        $return = $supplyneeds->_gridGilterNumberCallback($collection,$filter, $field);
        return $return;
    }

    protected function _filterDateCallback($collection, $column) {
        $filter = $column->getFilter()->getValue();
        $field = $this->_getRealFieldFromAlias($column->getIndex());
        $supplyneeds = Mage::getResourceModel('inventorysupplyneeds/inventorysupplyneeds');
        $return = $supplyneeds->_filterDateCallback($collection,$filter, $field);
        return $return;
    }

    protected function _getRealFieldFromAlias($alias) {
        $helperClass = $this->_helperClass;
        if (!$helperClass) {
            $filter = $this->getRequest()->getParam('top_filter');
            $helperClass = Mage::helper('inventorysupplyneeds');
            $helperClass->setTopFilter($filter);
        }
        $salesFromTo = $helperClass->getSalesFromTo();
        $getNumberDaysForecast = $helperClass->getNumberDaysForecast();
        $coreResource = Mage::getSingleton('core/resource');
        switch ($alias) {
            case 'product_sku':
                $field = 'catalog_product_entity.sku';
                break;
            case 'avg_qty_ordered':
                $field = "order_item.avg_qty_ordered";
                break;
            case 'total_qty_ordered':
                $field = 'order_item.total_qty_ordered';
                break;
            case 'total_available_qty':
                $field = new Zend_Db_Expr('SUM(main_table.available_qty)');
                break;
            case 'out_of_stock_date':
                $field = new Zend_Db_Expr("DATE_ADD(CURDATE(),INTERVAL(SUM(main_table.available_qty)/order_item.avg_qty_ordered) DAY)");
                break;
            case 'supplyneeds':
                $field = new Zend_Db_Expr("GREATEST((ROUND(SUM(order_item.qty_ordered)/{$salesFromTo['count']},2) * {$getNumberDaysForecast} - SUM(main_table.available_qty)),0)");
                break;
            case 'in_purchasing':
                $field = "tmp_po_product.inpurchasing";
                break;
        }
        return $field;
    }

    public function _getSelectedProducts() {
        $productArrays = $this->getProducts();
        $products = '';
        $supplierProducts = array();
        if ($productArrays) {
            $products = array();
            foreach ($productArrays as $productArray) {
                Mage::helper('inventoryplus')->parseStr(urldecode($productArray), $supplierProducts);
                if (!empty($supplierProducts)) {
                    foreach ($supplierProducts as $pId => $enCoded) {
                        $products[] = $pId;
                    }
                }
            }
        }
        return $products;
    }

    public function addExportType($url, $label) {
        if ($filter = $this->getRequest()->getParam('top_filter'))
            $exportUrl = $this->getUrl($url, array('_current' => false, 'top_filter' => $filter));
        else
            $exportUrl = $this->getUrl($url, array('_current' => false));
        $this->_exportTypes[] = new Varien_Object(
                array(
            'url' => $exportUrl,
            'label' => $label
                )
        );
        return $this;
    }

}