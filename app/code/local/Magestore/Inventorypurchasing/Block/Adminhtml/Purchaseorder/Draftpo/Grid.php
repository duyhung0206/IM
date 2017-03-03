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
class Magestore_Inventorypurchasing_Block_Adminhtml_Purchaseorder_Draftpo_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    protected $_helperClass;
    protected $_defaultLimit = 200;

    public function __construct() {
        parent::__construct();
        $this->setId('draftpoGrid');
        $this->setDefaultSort('draft_po_product_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        $this->setPagerVisibility(true);
        $this->_helperClass = $this->getHelperClass();
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel('inventorypurchasing/purchaseorder_draftpo_product')
                ->getCollection()
                ->addFieldToFilter('draft_po_id', $this->getRequest()->getParam('id'))
        ;
        $collection->getDraftpoCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $helperClass = $this->_helperClass;
        
        $this->addColumn('action',
            array(
                'header'    =>    Mage::helper('inventorypurchasing')->__('Action'),
                'width'        => '10px',
                'filter'    => false,
                'sortable'    => false,
                'index'        => 'stores',
                'is_system'    => true,
                'renderer' => 'inventorypurchasing/adminhtml_purchaseorder_draftpo_renderer_action',
        ));
        
        $this->addColumn('draft_po_product_id', array(
            'header' => Mage::helper('catalog')->__('ID'),
            'width' => '50px',
            'index' => 'draft_po_product_id',
            'column_css_class' => 'no-display',
            'header_css_class' => 'no-display'
        ));

        $this->addColumn('sku', array(
            'header' => Mage::helper('catalog')->__('SKU'),
            'width' => '100px',
            'index' => 'sku',
            'filter_condition_callback' => array($this, '_filterSkuCallback'),
        ));
        
        $this->addColumn('purchase_more', array(
            'header' => Mage::helper('inventorypurchasing')->__('Purchase Qty'),
            'name' => 'purchase_more',
            'type' => 'number',
            'width' => '50px',
            'renderer' => 'inventorypurchasing/adminhtml_purchaseorder_draftpo_renderer_purchasemore',
            'editable' => true,
        ));

        $this->addColumn('supplier_list', array(
            'header' => Mage::helper('inventorypurchasing')->__('Choose Supplier <br/> (Cost | Tax | Discount | Final Cost)'),
            'name' => 'supplier_list',
            'type' => 'text',
            'width' => '100px',
            'filter' => false,
            'sortable' => false,
            'renderer' => 'inventorypurchasing/adminhtml_purchaseorder_draftpo_renderer_supplierdropdown',
        ));

        $warehouseIds = $helperClass->getWarehouseSelected();

        foreach ($warehouseIds as $warehouseId) {
            $this->addColumn('warehouse_' . $warehouseId, array(
                'header' => 'Qty ordering for <br/>' . $this->getWarehouseById($warehouseId),
                'name' => 'warehouse_' . $warehouseId,
                'type' => 'number',
                'index' => 'warehouse_' . $warehouseId,
                'filter' => false,
                'editable' => true,
                'edit_only' => true,
                'sortable' => false,
                'warehouse_id' => $warehouseId,
                'width' => '50px',
                'renderer' => 'inventorypurchasing/adminhtml_purchaseorder_draftpo_renderer_warehouseqty',
            ));
        }
    }

    public function getWarehouseById($warehouseId) {
        $warehouse = Mage::getModel('inventoryplus/warehouse')->load($warehouseId);
        return $warehouse->getWarehouseName();
    }

    public function getGridUrl() {
        return $this->getUrl('*/*/viewpogrid', array(
                    '_current' => true,
                    'top_filter' => $this->getRequest()->getParam('top_filter')
        ));
    }

    protected function getHelperClass() {
        $helperClass = Mage::helper('inventorypurchasing/draftpo');
        return $helperClass;
    }

    public function getRowUrl($row) {
        return false;
    }

    public function _filterSkuCallback($collection, $column) {
        $filter = $column->getFilter()->getValue();
        $collection->getSelect()->where('product.sku like \'%' . $filter . '%\'');
        return $collection;
    }

    protected function _afterLoadCollection() {
        $this->removeColumn('draft_po_product_id');
        parent::_afterLoadCollection();
        if(Mage::helper('core')->isModuleEnabled('Magestore_Inventorysupplyneeds')) {
            Mage::helper('inventorysupplyneeds')->addWarehouseStaticToCollection($this->getCollection());
        }
        return $this;
    }

}
