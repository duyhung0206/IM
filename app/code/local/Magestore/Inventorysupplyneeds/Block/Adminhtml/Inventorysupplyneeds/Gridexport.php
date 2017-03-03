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
class Magestore_Inventorysupplyneeds_Block_Adminhtml_Inventorysupplyneeds_Gridexport
    extends Magestore_Inventoryplus_Block_Adminhtml_Widget_Grid {

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
            $this->_helperClass = $helperClass;
        }
        return $helperClass;
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
        $this->addColumn('product_sku', array(
            'header' => Mage::helper('inventoryplus')->__('SKU'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'sku'
        ));
        $helperClass = $this->getHelperClass();
        $warehouseSelected = $helperClass->getWarehouseSelected();
        if (count($warehouseSelected) > 1) {
            foreach ($warehouseSelected as $warehouseId) {
                $this->addColumn('avg_qty_ordered_' . $warehouseId, array(
                    'header' => Mage::helper('inventoryplus')->__('Qty. sold/day'),
                    'align' => 'right',
                    'index' => 'avg_qty_ordered_' . $warehouseId,
                    'type' => 'number'
                ));
                $this->addColumn('total_qty_ordered_' . $warehouseId, array(
                    'header' => Mage::helper('inventoryplus')->__('Total Sold'),
                    'align' => 'right',
                    'index' => 'total_qty_ordered_' . $warehouseId,
                    'type' => 'number'
                ));
                $this->addColumn('available_qty_' . $warehouseId, array(
                    'header' => Mage::helper('inventoryplus')->__('Avail. Qty'),
                    'align' => 'right',
                    'index' => 'available_qty_' . $warehouseId,
                    'type' => 'number'
                ));
				if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorypurchasing')) {
					$this->addColumn('in_purchasing_' . $warehouseId, array(
						'header' => Mage::helper('inventoryplus')->__('In Purchasing'),
						'align' => 'right',
						'sortable' => false,
						'filter' => false,
						'width' => '80px',
						'index' => 'in_purchasing_' . $warehouseId
					));
				}
                $this->addColumn('out_of_stock_date_' . $warehouseId, array(
                    'header' => Mage::helper('inventoryplus')->__('Out-of-stock Date'),
                    'align' => 'left',
                    'index' => 'out_of_stock_date_' . $warehouseId,
                    'type' => 'date'
                ));
                $this->addColumn('supplyneeds_' . $warehouseId, array(
                    'header' => Mage::helper('inventoryplus')->__('Supply Needs'),
                    'align' => 'right',
                    'index' => 'purchase_more_' . $warehouseId,
                    'type' => 'number'
                ));
            }
        }
        $this->addColumn('avg_qty_ordered', array(
            'header' => Mage::helper('inventoryplus')->__('Qty. sold/day'),
            'align' => 'right',
            'index' => 'avg_qty_ordered',
            'type' => 'number'
        ));
        $this->addColumn('total_qty_ordered', array(
            'header' => Mage::helper('inventoryplus')->__('Total Sold'),
            'align' => 'right',
            'index' => 'total_qty_ordered',
            'type' => 'number'
        ));
        $this->addColumn('total_available_qty', array(
            'header' => Mage::helper('inventoryplus')->__('Avail. Qty'),
            'align' => 'right',
            'index' => 'total_available_qty',
            'type' => 'number'
        ));
		if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorypurchasing')) {
			$this->addColumn('in_purchasing', array(
				'header' => Mage::helper('inventoryplus')->__('Qty On Order'),
				'align' => 'right',
				'index' => 'in_purchasing',
				'sortable' => false,
				'filter' => false,
				'width' => '80px'
			));
		}
        $this->addColumn('out_of_stock_date', array(
            'header' => Mage::helper('inventoryplus')->__('Out-of-stock Date'),
            'align' => 'left',
            'index' => 'out_of_stock_date',
            'type' => 'date'
        ));
        $this->addColumn('supplyneeds', array(
            'header' => Mage::helper('inventoryplus')->__('Supply Needs'),
            'align' => 'right',
            'index' => 'purchase_more',
            'type' => 'number'
        ));

        return parent::_prepareColumns();
    }

    public function _prepareCollectionInContruct($helperClass) {
        $dateto = $helperClass->getForecastTo();
        $salesFromTo = $helperClass->getSalesFromTo();
        $warehouseSelected = $helperClass->getWarehouseSelected();
        $supplierSelected = $helperClass->getsupplierSelected();
        $historySelected = $helperClass->getHistorySelected();
        $getNumberDaysForecast = $helperClass->getNumberDaysForecast();
        $purchase_more_rate = $helperClass->getRatePurchaseMore();
        $rate = $purchase_more_rate / 100;
        $coreResource = Mage::getSingleton('core/resource');
        if (count($warehouseSelected) > 1) { // If select MULTI warehouses in supplyneeds page
            foreach ($warehouseSelected as $warehouseId) {
                $listItemIds = $this->getListOrderItemIds($salesFromTo, array($warehouseId));
                $collectionWarehouse = $this->getTemporaryCollection($salesFromTo, array($warehouseId), $supplierSelected, $getNumberDaysForecast, $rate, false);
                $_temp_sql = " DROP TABLE IF EXISTS " . $coreResource->getTableName('supplyneeds_step_' . $warehouseId) . ";";
                $_temp_sql .= " CREATE TEMPORARY TABLE " . $coreResource->getTableName('supplyneeds_step_' . $warehouseId) . " ("; // CREATE TEMPORARY TABLE
                $_temp_sql .= $collectionWarehouse->getSelect()->__toString() . ")";
                Mage::getSingleton('core/resource')->getConnection('core_write')->query($_temp_sql);
            }
            $collection = $this->getTemporaryCollection($salesFromTo, $warehouseSelected, $supplierSelected, $getNumberDaysForecast, $rate, true);
            foreach ($warehouseSelected as $warehouseId) {
                $collection->getSelect()
                        ->joinLeft(
                                array('temp_supplyneeds_' . $warehouseId => $coreResource->getTableName('supplyneeds_step_' . $warehouseId)), "main_table.product_id=temp_supplyneeds_{$warehouseId}.product_id", array('temp_supplyneeds_' . $warehouseId . '.*'));
            }
        } else { 
            // If select ONE warehouse in supplyneeds page
            $collection = $this->getTemporaryCollection($salesFromTo, $warehouseSelected, $supplierSelected, $getNumberDaysForecast, $rate, true);
        }
        $this->_collectionGrid = $collection;
    }

    protected function getTemporaryCollection($salesFromTo, $warehouseSelected, $supplierSelected, $getNumberDaysForecast, $rate, $getSku) {
        if (count($warehouseSelected) == 1 && $getSku != true)
            $postfix = "_{$warehouseSelected[0]}";
        else
            $postfix = "";
        $listItemIds = $this->getListOrderItemIds($salesFromTo, $warehouseSelected);
        $tempTableArr = array('export_supplier_temp_table', 'export_order_item_temp_table', 'export_purchase_order_product_temp');
        $this->removeTempTables($tempTableArr);
		$w_productCol = $this->getWarehouseProductCollection($warehouseSelected, $getSku);
        if ($listItemIds) {
            $orderItemCol = $this->getOrderItemCollection($listItemIds, $salesFromTo, $warehouseSelected, $getSku);
            $this->createTempTable('export_order_item_temp_table', $orderItemCol);
        }
		if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorypurchasing')) {			
			$s_productCol = $this->getSupplierProductCollection($supplierSelected);
			$this->createTempTable('export_supplier_temp_table', $s_productCol);
			$poProductCol = $this->getPOProductCollection($warehouseSelected, $supplierSelected, $getSku);
			$this->createTempTable('export_purchase_order_product_temp', $poProductCol);
		}
        $coreResource = Mage::getSingleton('core/resource');
        $collection = $w_productCol;
		if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorypurchasing')) {
			$collection->getSelect()
					->join(
							array('supplier_product' => $coreResource->getTableName('export_supplier_temp_table')), "main_table.product_id=supplier_product.product_id", array('supplier_product.all_supplier_id'));
			$collection->getSelect()
						->joinLeft(
								array('tmp_po_product' => $coreResource->getTableName('export_purchase_order_product_temp')), "main_table.product_id=tmp_po_product.product_id", array('in_purchasing' . $postfix => new Zend_Db_Expr("IFNULL(tmp_po_product.in_purchasing{$postfix},0)")));
		}
        if ($listItemIds) {
            $collection->getSelect()
                    ->join(
                            array('order_item' => $coreResource->getTableName('export_order_item_temp_table')), "main_table.product_id=order_item.product_id", array('order_item.total_qty_ordered' . $postfix, 'order_item.avg_qty_ordered' . $postfix));            
            $collection->getSelect()->columns(array(
                'out_of_stock_date' . $postfix => new Zend_Db_Expr("DATE_ADD(CURDATE(),INTERVAL(SUM(main_table.available_qty)/order_item.avg_qty_ordered{$postfix}) DAY)"),
                'supplyneeds' . $postfix => new Zend_Db_Expr("GREATEST((order_item.avg_qty_ordered{$postfix} * {$getNumberDaysForecast} - SUM(main_table.available_qty)),0)")));
			if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorypurchasing')) {
				$collection->getSelect()->columns(array(
					'purchase_more' . $postfix => new Zend_Db_Expr("CEIL(GREATEST(((order_item.avg_qty_ordered{$postfix} * {$getNumberDaysForecast} - SUM(main_table.available_qty))* {$rate} - IFNULL(tmp_po_product.in_purchasing{$postfix},0)),0))"),
				));
			}
        } else {
            $collection->getSelect()->columns(array(
                'out_of_stock_date' . $postfix => new Zend_Db_Expr('IF((SUM(main_table.product_id)*0)=0,NULL,(SUM(main_table.product_id)*0))'),
                'supplyneeds' . $postfix => new Zend_Db_Expr('SUM(main_table.product_id)*0'),
                'total_qty_ordered' . $postfix => new Zend_Db_Expr('SUM(main_table.product_id)*0'),
                'avg_qty_ordered' . $postfix => new Zend_Db_Expr('SUM(main_table.product_id)*0')));
			if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorypurchasing')) {
				$collection->getSelect()->columns(array(
					'purchase_more' . $postfix => new Zend_Db_Expr('SUM(main_table.product_id)*0')
				));
			}
        }
        return $collection;
    }

    protected function getListOrderItemIds($salesFromTo, $warehouseSelected) {
        $coreResource = Mage::getSingleton('core/resource');
        $coreResource->getConnection('core_write')->query('SET SESSION group_concat_max_len = 1000000;');
        $warehouseSelectedStr = implode(',', $warehouseSelected);
        $conditionOne = "order_item.created_at > '{$salesFromTo['from']}' AND order_item.created_at < '{$salesFromTo['to']}' AND main_table.warehouse_id IN ({$warehouseSelectedStr})";
        $warehouseOrder = Mage::getModel('inventoryplus/warehouse_order')->getCollection();
        $warehouseOrder->getSelect()
                ->joinLeft(
                        array('order_item' => $warehouseOrder->getTable('sales/order_item')), "main_table.item_id=order_item.item_id", array('item_id'));
        $warehouseOrder->getSelect()->where($conditionOne);
        $warehouseOrder->getSelect()->columns(array(
            'all_item_id' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT main_table.item_id SEPARATOR ",")')));
        $itemIds = $warehouseOrder->setPageSize(1)->setCurPage(1)->getFirstItem()->getAllItemId();
        return $itemIds;
    }

    protected function getWarehouseProductCollection($warehouseSelected, $getSku) {
        $supplyneeds = Mage::getResourceModel('inventorysupplyneeds/inventorysupplyneeds');
        $collection = $supplyneeds->gridExportGetWarehouseProductCollection($warehouseSelected, $getSku);           
        return $collection;
    }

    protected function getSupplierProductCollection($supplierSelected) {
        $supplyneeds = Mage::getResourceModel('inventorysupplyneeds/inventorysupplyneeds');
        //get last purchased suppliers
        $collection = $supplyneeds->gridExportGetSupplierProductCollection($supplierSelected);
        return $collection;
    }

    protected function getOrderItemCollection($listItemIds, $salesFromTo, $warehouseSelected, $getSku) {
        $helperClass = $this->getHelperClass();
        $supplyneeds = Mage::getResourceModel('inventorysupplyneeds/inventorysupplyneeds');
        $supplyneeds->setHelperClass($helperClass);
        $collection = $supplyneeds->gridExportGetOrderItemCollection($listItemIds, $salesFromTo, $warehouseSelected, $getSku);           
        return $collection;
    }

    protected function getPOProductCollection($warehouseSelected, $supplierSelected, $getSku) {
        $supplyneeds = Mage::getResourceModel('inventorysupplyneeds/inventorysupplyneeds');
        $collection = $supplyneeds->gridExportGetPOProductCollection($warehouseSelected, $supplierSelected, $getSku);           
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

    public function getGridUrl() {
        return false;
    }

    public function getRowUrl($row) {
        return false;
    }

    /**
     * Get loaded collection
     * 
     * @return collection
     */
    public function getCollectionData($productIds = array()) {
        $this->_isExport = true;
        $this->_prepareGrid();
        if (count($productIds)) {
            $this->getCollection()->addFieldToFilter('main_table.product_id', array('in' => $productIds));
        }
        $this->getCollection()->getSelect()->limit();
        $this->getCollection()->setPageSize(0);
        $this->getCollection()->load();
        $this->_afterLoadCollection();
        return $this->getCollection();
    }

    public function getFirstRowCsv() {
        $return = '""';
        $helperClass = $this->getHelperClass();
        $warehouseSelected = $helperClass->getWarehouseSelected();
        if (count($warehouseSelected) > 1) {
            foreach ($warehouseSelected as $warehouseId) {
                $warehouseName = Mage::getModel('inventoryplus/warehouse')->load($warehouseId)->getWarehouseName();
                $return .= ',"' . $warehouseName . '",""' . ',""' . ',""' . ',""' . ',""';
            }
            $return .= ',"' . $this->__('Total') . '",""' . ',""' . ',""' . ',""' . ',""';
        }
        return $return;
    }

    public function getCsv() {
        $csv = '';
        $this->_isExport = true;
        $this->_prepareGrid();
        $this->getCollection()->getSelect()->limit();
        $this->getCollection()->setPageSize(0);
        $this->getCollection()->load();
        $this->_afterLoadCollection();

        $data = array();
        $csv .= $this->getFirstRowCsv() . "\n";
        foreach ($this->_columns as $column) {
            if (!$column->getIsSystem()) {
                $data[] = '"' . $column->getExportHeader() . '"';
            }
        }
        $csv.= implode(',', $data) . "\n";

        foreach ($this->getCollection() as $item) {
            $data = array();
            foreach ($this->_columns as $column) {
                if (!$column->getIsSystem()) {
                    $data[] = '"' . str_replace(array('"', '\\'), array('""', '\\\\'), $column->getRowFieldExport($item)) . '"';
                }
            }
            $csv.= implode(',', $data) . "\n";
        }

        if ($this->getCountTotals()) {
            $data = array();
            foreach ($this->_columns as $column) {
                if (!$column->getIsSystem()) {
                    $data[] = '"' . str_replace(array('"', '\\'), array('""', '\\\\'), $column->getRowFieldExport($this->getTotals())) . '"';
                }
            }
            $csv.= implode(',', $data) . "\n";
        }

        return $csv;
    }

}
