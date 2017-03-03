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
 * Inventorybarcode Adminhtml Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventorybarcode
 * @author      Magestore Developer
 */

class Magestore_Inventorybarcode_Block_Adminhtml_Barcode_Edit_Tab_Printbarcode extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('barcodeGrid');
        $this->setDefaultSort('barcode_id');
        $this->setDefaultDir('DESC');
        $this->setDefaultFilter(array('in_products' => 1));
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    /**
     * prepare collection for block to display
     *
     * @return Magestore_Inventorybarcode_Block_Adminhtml_Barcode_Grid
     */
    protected function _prepareCollection() {
        $barcodeIds = $this->getRequest()->getParam('barcodeIds');
        if ($barcodeIds) {
            $barcodeIds = explode(',', $barcodeIds);
        }
        if ($this->helper('inventorybarcode')->isMultipleBarcode()) {
            /* multiple barcode mode */
            $collection = Mage::getModel('inventorybarcode/barcode')->getCollection();

        } else {
            /* Single barcode mode */
            $collection = Mage::getResourceModel('inventoryplus/product_collection');
            $collection->addAttributeToSelect('name')
                    ->addAttributeToSelect('status')
                    ->addAttributeToSelect('price')
                    ->addAttributeToSelect('attribute_set_id')
                    ->addAttributeToFilter('type_id', array('nin' => array('configurable', 'bundle', 'grouped')));
            
            $collection->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');
            
            if ($this->getBarcodeAttribute() != 'sku') {
                $collection->addAttributeToSelect($this->getBarcodeAttribute());
            }

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

        if ($this->helper('inventorybarcode')->isMultipleBarcode()) {
            $this->_prepareColumnsMultipleBarcode();
        } else {
            $this->_prepareColumnsSingleBarcode();
        }
        return $this;
    }

    protected function _prepareColumnsSingleBarcode() {

        $this->addColumn('in_products', array(
            'header_css_class' => 'a-center',
            'type' => 'checkbox',
            'name' => 'in_products',
            'values' => $this->_getSelectedBarcodes(),
            'align' => 'center',
            'index' => 'entity_id',
                //'use_index' => true,
        ));

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
        
        $this->addColumn('print_qty', array(
            'header' => Mage::helper('inventoryplus')->__('Qty to print'),
            'align' => 'right',
            'index' => 'qty',
            'type' => 'number',
            'editable' => true,
            'sortable' => false,
            'filter' => false,
        ));        


        $this->addExportType('*/*/exportCsv', Mage::helper('inventorybarcode')->__('CSV'));
        $this->addExportType('*/*/exportXml', Mage::helper('inventorybarcode')->__('XML'));

        return parent::_prepareColumns();
    }

    protected function _prepareColumnsMultipleBarcode() {

        $this->addColumn('in_products', array(
            'header_css_class' => 'a-center',
            'type' => 'checkbox',
            'name' => 'in_products',
            'values' => $this->_getSelectedBarcodes(),
            'align' => 'center',
            'index' => 'barcode_id',
            //'use_index' => true,
        ));

        $this->addColumn('barcode_id', array(
            'header' => Mage::helper('inventorybarcode')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'barcode_id',
        ));

        $this->addColumn('barcode', array(
            'header' => Mage::helper('inventorybarcode')->__('Barcode'),
            'align' => 'left',
            'index' => 'barcode',
        ));

        $barcodeAttributes = Mage::getModel('inventorybarcode/barcodeattribute')->getCollection()
                ->addFieldToFilter('attribute_status', 1)
                ->addFieldToFilter('attribute_display', 1);
        foreach ($barcodeAttributes as $barcodeAttribute) {
            $this->addColumn($barcodeAttribute->getAttributeCode(), array(
                'header' => $barcodeAttribute->getAttributeName(),
                'align' => 'left',
                'index' => $barcodeAttribute->getAttributeCode(),
            ));
        }

        $this->addColumn('print_qty', array(
            'header' => Mage::helper('inventoryplus')->__('Qty to print'),
            'align' => 'right',
            'index' => 'qty',
            'type' => 'number',
            'editable' => true,
            'sortable' => false,
            'filter' => false
        ));

        /*
        $this->addColumn('created_date', array(
            'header' => Mage::helper('inventorybarcode')->__('Created Date'),
            'align' => 'left',
            'index' => 'created_date',
            'type' => 'datetime'
        ));

        $this->addColumn('barcode_status', array(
            'header' => Mage::helper('inventorybarcode')->__('Status'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'barcode_status',
            'type' => 'options',
            'options' => array(
                1 => 'Enabled',
                0 => 'Disabled',
            ),
        ));
        */
        
        return parent::_prepareColumns();
    }

    protected function _addColumnFilterToCollection($column) {
        if ($column->getId() == 'in_products') {
            $barcodeIds = $this->_getSelectedBarcodes();
            if (empty($barcodeIds)) {
                $barcodeIds = 0;
            }
            if ($this->helper('inventorybarcode')->isMultipleBarcode()) {
                /* multiple barcode mode */
                $idField = 'barcode_id';
            } else {
                /* single barcode mode */
                $idField = 'entity_id';
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter($idField, array('in' => $barcodeIds));
            } else {
                if ($barcodeIds) {
                    $this->getCollection()->addFieldToFilter($idField, array('nin' => $barcodeIds));
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    public function _getSelectedBarcodes() {
        $barcodeIds = array();
        $barcodes = $this->getBarcodes();
        if (count($barcodes)) {
            foreach ($barcodes as $barcodeData) {
                Mage::helper('inventoryplus')->parseStr(urldecode($barcodeData), $barcodeData);
                if (!empty($barcodeData)) {
                    foreach ($barcodeData as $barcodeId => $enCoded) {
                        $barcodeIds[] = $barcodeId;
                    }
                }
            }
        }
        if (!is_array($barcodes)) {
            $barcodeIds = array_keys($this->getSelectedBarcodes());
        }
        return $barcodeIds;
    }

    public function getSelectedBarcodes() {
        $barcodes = array();
        $barcodeIds = $this->getRequest()->getParam('barcodeIds');
        $barcodeIds = explode(',', $barcodeIds);
        $barcodes = $this->_getPrintQtyData($barcodeIds);
        return $barcodes;
    }
    
    protected function _getPrintQtyData($barcodeIds) {
        $printQtyData = array();
        if ($this->helper('inventorybarcode')->isMultipleBarcode()) {
            /* multiple barcode mode */
            $collection = Mage::getModel('inventorybarcode/barcode')->getCollection();
            $collection->addFieldToFilter('barcode_id', array('in' => $barcodeIds));
        } else {
            /* Single barcode mode */
            $collection = Mage::getResourceModel('inventoryplus/product_collection');
            $collection->joinField('qty',
                'cataloginventory/stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left');
              $collection->addFieldToFilter('entity_id', array('in' => $barcodeIds));
        }
        if(count($collection)){
            foreach($collection as $item) {
                $printQtyData[$item->getId()] = array('print_qty' => floatval($item->getQty()));
            }
        }
        return $printQtyData;
    }
    
    public function getGridUrl() {
        return $this->getUrl('*/*/printbarcodegrid', array('_secure' => true));
    }

    public function getBarcodeAttribute() {
        return $this->helper('inventorybarcode')->getBarcodeAttribute();
    }

}
