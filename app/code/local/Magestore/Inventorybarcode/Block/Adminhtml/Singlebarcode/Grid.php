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
 * @package     Magestore_Inventorybarcode
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Inventorybarcode Grid Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventorybarcode
 * @author      Magestore Developer
 */
class Magestore_Inventorybarcode_Block_Adminhtml_Singlebarcode_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('singlebarcodeGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * prepare collection for block to display
     *
     * @return Magestore_Inventorybarcode_Block_Adminhtml_Barcode_Grid
     */
    protected function _prepareCollection() {
        $collection = Mage::getResourceModel('inventoryplus/product_collection');
        $collection->addAttributeToSelect('name')
                ->addAttributeToSelect('status')
                ->addAttributeToSelect('price')
                ->addAttributeToSelect('attribute_set_id')
                ->addAttributeToSelect('type_id')
                ->addAttributeToFilter('type_id', array('nin' => array('configurable', 'bundle', 'grouped')));
        if ($this->getBarcodeAttribute() != 'sku') {
            $collection->addAttributeToSelect($this->getBarcodeAttribute());
            $collection->addAttributeToFilter($this->getBarcodeAttribute(), array('neq'=>''));
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * prepare columns for this grid
     *
     * @return Magestore_Inventorybarcode_Block_Adminhtml_Barcode_Grid
     */
    protected function _prepareColumns() {

        $this->addColumn('entity_id', array(
            'header' => Mage::helper('catalog')->__('ID'),
            'sortable' => true,
            'width' => '50',
            'type' => 'number',
            'index' => 'entity_id',
        ));

        $this->addColumn($this->getBarcodeAttribute(), array(
            'header' => Mage::helper('catalog')->__('Barcode'),
            'index' => $this->getBarcodeAttribute()
        ));

        $this->addColumn('product_name', array(
            'header' => Mage::helper('catalog')->__('Product Name'),
            'align' => 'left',
            'index' => 'name',
        ));

        $this->addColumn('product_sku', array(
            'header' => Mage::helper('catalog')->__('SKU'),
            'index' => 'sku'
        ));

        $sets = Mage::getResourceModel('eav/entity_attribute_set_collection')
                ->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId())
                ->load()
                ->toOptionHash();

        $this->addColumn('set_name', array(
            'header' => Mage::helper('catalog')->__('Attrib. Set Name'),
            'width' => '100px',
            'index' => 'attribute_set_id',
            'type' => 'options',
            'options' => $sets,
        ));

        $this->addColumn('action',
            array(
                'header'    =>    Mage::helper('inventorybarcode')->__('Action'),
                'width'        => '100',
                'type'        => 'action',
                'filter'    => false,
                'sortable'    => false,
                'index'        => 'stores',
                'is_system'    => true,
                'renderer' => 'inventorybarcode/adminhtml_barcode_edit_renderer_action'
        ));        

        $this->addExportType('*/*/exportCsv', Mage::helper('inventorybarcode')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('inventorybarcode')->__('XML'));

        return parent::_prepareColumns();
    }

    /**
     * prepare mass action for this grid
     *
     * @return Magestore_Inventorybarcode_Block_Adminhtml_Inventorybarcode_Grid
     */
    protected function _prepareMassaction() {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('barcodeIds');      
        $this->getMassactionBlock()->addItem('print_barcode', array(
            'label' => Mage::helper('inventorybarcode')->__('Go to Mass Print'),
            'url' => $this->getUrl('adminhtml/inb_barcode/edit', array('print' => 'true'))
        ));
        return $this;
    }

    public function getBarcodeAttribute() {
        return $this->helper('inventorybarcode')->getBarcodeAttribute();
    }
    

    public function getXml()
    {
        $this->_isExport = true;
        $this->_prepareGrid();
        $this->getCollection()->getSelect()->limit();
        $this->getCollection()->setPageSize(0);
        $this->getCollection()->load();
        $this->_afterLoadCollection();
        $indexes = array();
        foreach ($this->_columns as $column) {
            if (!$column->getIsSystem()) {
                $indexes[] = $column->getIndex();
            }
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml.= '<items>';
        foreach ($this->getCollection() as $item) {
            $item->setData('stock_item',null);
            $xml.= $item->toXml($indexes);
        }
        if ($this->getCountTotals())
        {
            $xml.= $this->getTotals()->toXml($indexes);
        }
        $xml.= '</items>';
        return $xml;
    }    

}
