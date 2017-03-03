<?php
class Magestore_Inventorylowstock_Block_Adminhtml_Inventorysupplyneeds_Gridexport
    extends Magestore_Inventoryplus_Block_Adminhtml_Widget_Grid {

    protected $_helperClass = null;
    protected $_collectionGrid = null;

    public function __construct() {
        parent::__construct();
        $this->setId('inventorylowstockGrid');
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

    protected function getHelperClass() {
        $helperClass = $this->_helperClass;
        if (!$helperClass) {
            $filter = $this->getRequest()->getParam('top_filter');
            $helperClass = Mage::helper('inventorylowstock/supplyneeds');
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
                $tempTableArr = array('order_item_temp_table');
                $this->removeTempTables($tempTableArr);
                $this->createTempTable('order_item_temp_table', $orderItemCol);
                $collection = $w_productCol;
                
                $collection->getSelect()
                        ->join(
                                array('order_item' => $coreResource->getTableName('order_item_temp_table')), "main_table.product_id=order_item.product_id", array('order_item.*'));
               
                $collection->getSelect()->columns(array(
                    'out_of_stock_date' => new Zend_Db_Expr("DATE_ADD(CURDATE(),INTERVAL(SUM(main_table.available_qty)/order_item.avg_qty_ordered) DAY)"),
                    'supplyneeds' => new Zend_Db_Expr("GREATEST((order_item.avg_qty_ordered * {$getNumberDaysForecast} - SUM(main_table.available_qty)),0)")
                ));
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

    protected function _prepareColumns() {
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
        $this->addColumn('product_sku', array(
            'header' => Mage::helper('inventoryplus')->__('SKU'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'product_sku'
        ));
        $this->addColumn('avg_qty_ordered', array(
            'header' => Mage::helper('inventoryplus')->__('Qty. Ordered/day'),
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
            'header' => Mage::helper('inventoryplus')->__('Avail. Qty'), // Dang bi SAI
            'align' => 'right',
            'index' => 'total_available_qty',
            'type' => 'number'
        ));

        $this->addColumn('out_of_stock_date', array(
            'header' => Mage::helper('inventoryplus')->__('Out-of-stock Date'),
            'align' => 'left',
            'index' => 'out_of_stock_date',
            'type' => 'date'
        ));
        $this->addColumn('supplyneeds', array(
            'header' => Mage::helper('inventoryplus')->__('Supply Needs'),
            'align' => 'right',
            'index' => 'supplyneeds',
            'type' => 'number'
        ));
        if (!$this->_isExport) {
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
        return parent::_prepareColumns();
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
        $supplyneeds = Mage::getResourceModel('inventorylowstock/inventorysupplyneeds');
        $collection = $supplyneeds->gridGetWarehouseProductCollection($helperClass);
        return $collection;
    }


    protected function getNumberHoursFromTwoDate($from, $to) {
        $hours = round((strtotime($to) - strtotime($from)) / (60 * 60));
        return $hours;
    }

    protected function getOrderItemCollection($listItemIds, $helperClass) {
        $supplyneeds = Mage::getResourceModel('inventorylowstock/inventorysupplyneeds');
        $collection = $supplyneeds->gridGetOrderItemCollection($listItemIds, $helperClass);
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