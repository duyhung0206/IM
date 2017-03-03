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
 * @package     Magestore_Inventorypurchasing
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

class Magestore_Inventorypurchasing_Block_Adminhtml_Purchaseorder_Lowstock_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    
    const LOW_STOCK_QTY_XML_PATH = 'cataloginventory/item_options/notify_stock_qty';
    
    public function __construct() {
        parent::__construct();
        $this->setId('lowstockGrid');
        $this->setDefaultSort('purchase_qty');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
        //$this->setDefaultFilter(array('in_products' => 1));
    }
    
    protected function _addColumnFilterToCollection($column) {
        if ($column->getId() == 'in_products') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds))
                $productIds = 0;
            if ($column->getFilter()->getValue())
                $this->getCollection()->addFieldToFilter('entity_id', array('in' => $productIds));
            elseif ($productIds)
                $this->getCollection()->addFieldToFilter('entity_id', array('nin' => $productIds));
            return $this;
        }
        return parent::_addColumnFilterToCollection($column);
    }    

    /**
     * prepare collection for block to display
     *
     * @return Magestore_Inventorylowstock_Block_Adminhtml_Lowstock_Grid
     */
    protected function _prepareCollection() {
        $supplierIds = $this->_helper()->getCurrentSupplierIds();
        $warehouseIds = $this->_helper()->getCurrentWarehouseIds();
        $lowStockQty = Mage::getStoreConfig(self::LOW_STOCK_QTY_XML_PATH);
        //$lowStockQty *= count($warehouseIds);
        $productIds = $this->helper('inventorypurchasing/supplier')->getProductIdsFromSuppliers($supplierIds);
        
        $collection = Mage::getResourceModel('inventorypurchasing/product_collection')
                                ->getLowStockByProductIds($productIds, $warehouseIds, $lowStockQty, $supplierIds);   
      
        $this->_sortCollection($collection);
        $this->setCollection($collection); 
        
        return parent::_prepareCollection();
    }

    /**
     * prepare columns for this grid
     *
     * @return Magestore_Inventorylowstock_Block_Adminhtml_Lowstock_Grid
     */
    protected function _prepareColumns() {
        
        $this->addColumn('in_products', array(
            'header_css_class' => 'a-center',
            'type' => 'checkbox',
            'name' => 'in_products',
            'values' => $this->_getSelectedProducts(),
            'align' => 'center',
            'index' => 'entity_id',
            'use_index' => true,
            'is_system' => true,
        ));
        
        
        $this->addColumn('entity_id', array(
            'header' => Mage::helper('inventoryplus')->__('ID'),
            'align' => 'right',
            'width' => '10px',
            'type' => 'number',
            'index' => 'entity_id',
        ));
        
        $this->addColumn('name', array(
            'header' => Mage::helper('inventoryplus')->__('Name'),
            'type' => 'text',
            'index' => 'name',
        ));        

        $this->addColumn('sku', array(
            'header' => Mage::helper('inventoryplus')->__('SKU'),
            'type' => 'text',
            'index' => 'sku',
        ));    
        
        /*
        $this->addColumn('supplier_list', array(
            'header' => Mage::helper('inventoryplus')->__('Suppliers'),
            'type' => 'text',
            'index' => 'supplier_list',
            'width' => '200px',
            'renderer' => 'inventorypurchasing/adminhtml_purchaseorder_lowstock_renderer_supplierdropdown',
        ));   
        */
        
        $this->addColumn('available_qty', array(
            'header' => Mage::helper('inventoryplus')->__('Avail. Qty'),
            'align' => 'right',
            'type' => 'number',
            'index' => 'available_qty',
            'filter_condition_callback' => array($this, '_filterNumberCallback'),
        ));        
        
        $this->addColumn('lowstock_qty', array(
            'header' => Mage::helper('inventoryplus')->__('Low Stock Qty'),
            'align' => 'right',
            'type' => 'number',
            'index' => 'lowstock_qty',
            'filter_condition_callback' => array($this, '_filterNumberCallback'),
        ));      
        
        
        $this->addColumn('purchasing_qty', array(
            'header' => Mage::helper('inventoryplus')->__('In-Purchasing Qty'),
            'align' => 'right',
            'type' => 'number',
            'index' => 'purchasing_qty',
            'filter_condition_callback' => array($this, '_filterNumberCallback'),
        ));   
        
        $this->addColumn('purchase_qty', array(
            'header' => Mage::helper('inventoryplus')->__('Purchase Qty'),
            'align' => 'right',
            'type' => 'number',
            'index' => 'purchase_qty',
            //'editable' => true,
            'filter_condition_callback' => array($this, '_filterNumberCallback'),
        ));    
        /*
        $this->addColumn('clone_input', array(
            'header' => Mage::helper('inventoryplus')->__('Qty'),
            'align' => 'right',
            'type' => 'number',
            'index' => 'clone_input',
            'editable' => true,
        ));         
        */
        if(0){
            $this->addColumn('action',
                array(
                    'header'    =>    Mage::helper('inventoryplus')->__('Action'),
                    'width'        => '100',
                    'type'        => 'action',
                    'getter'    => 'getId',
                    'actions'    => array(
                        array(
                            'caption'    => Mage::helper('inventoryplus')->__('Orders List'),
                            'url'        => array('base'=> '*/*/orders'),
                            'field'        => 'id'
                        )),
                    'filter'    => false,
                    'sortable'    => false,
                    'index'        => 'stores',
                    'is_system'    => true,
            ));
        }
        $this->addExportType('*/*/exportCsv', Mage::helper('inventoryplus')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('inventoryplus')->__('XML'));

        return parent::_prepareColumns();
    }

    /**
     * get url for each row in grid
     *
     * @return string
     */
    public function getRowUrl($row) {
        return false;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid');
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
        if (isset($filter['from'])) {
            $collection->getSelect()->having($field . ' >= \'' . $filter['from'] . '\'');
        }
        if (isset($filter['to'])) {
            $collection->getSelect()->having($field . ' <= \'' . $filter['to'] . '\'');
        }
        return $collection;
    }

    /**
     * Sort collection
     * 
     * @param collection $collection
     * @return collection
     */
    protected function _sortCollection($collection) {
        $sort = $this->getRequest()->getParam('sort', $this->_defaultSort);
        $dir = $this->getRequest()->getParam('dir', $this->_defaultDir);
        $this->setCollection($collection);
        $field = $sort;
        if ($field) {
            $collection->getSelect()->order("$field $dir");
        }
        return $collection;
    }

    /**
     * Get real filed from alias in sql
     * 
     * @param string $alias
     * @return string
     */
    protected function _getRealFieldFromAlias($alias){
        $field = $alias;
        switch($alias){
            case 'available_qty':
                $field = "SUM(productWH.available_qty)";
                break;            
            case 'lowstock_qty':
                $lowStockQty = Mage::getStoreConfig(self::LOW_STOCK_QTY_XML_PATH);
                $field = "SUM(IF(stockItem.use_config_notify_stock_qty = 1, $lowStockQty, stockItem.notify_stock_qty))";
                break;  
            case 'purchasing_qty':
                $field = "SUM(IFNULL(poProductWH.qty_order - poProductWH.qty_received, 0))";
                break;
            case 'purchase_qty':
                $lowStockQty = Mage::getStoreConfig(self::LOW_STOCK_QTY_XML_PATH);
                $field = "SUM(IF(stockItem.use_config_notify_stock_qty = 1, $lowStockQty, stockItem.notify_stock_qty) - productWH.available_qty) - SUM(IFNULL(poProductWH.qty_order - poProductWH.qty_received, 0))";
                break;
        }
        
        if($field != $alias){
            $field = new Zend_Db_Expr($field);
        }
        return $field;        
    } 
    
    public function loadCollection() {
        $this->_prepareCollection();
    }

    public function _getSelectedProducts() {
        $productArrays = $this->getProducts();
        $products = '';
        $warehouseProducts = array();
        if ($productArrays) {
            $products = array();
            foreach ($productArrays as $productArray) {
                Mage::helper('inventoryplus')->parseStr(urldecode($productArray), $warehouseProducts);
                if (count($warehouseProducts)) {
                    foreach ($warehouseProducts as $pId => $enCoded) {
                        $products[] = $pId;
                    }
                }
            }
        }
        if (!is_array($products)) {
            $products = array_keys($this->getSelectedProducts());
        }
        return $products;
    }
    
    public function getSelectedProducts() {
        $products = array();
        //$supplierIds = $this->_helper()->getCurrentSupplierIds();
        //$productIds = $this->helper('inventorypurchasing/supplier')->getProductIdsFromSuppliers($supplierIds);
        $productIds = array();
        if(count($productIds)) {
            foreach($productIds as $productId) {
                $products[$productId] = array('clone_input' => 0);
            }
        }
        return $products;        
    }
    
    protected function _helper() {
        return $this->helper('inventorypurchasing/lowstock');
    }

}