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
 * Inventoryreports Adminhtml Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventoryreports
 * @author      Magestore Developer
 */
class Magestore_Inventoryreports_Block_Adminhtml_Inventoryreports_Adjust_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public static function cmpAscDate($a, $b) {
        return $a->getDate() > $b->getDate();
    }

    public static function cmpDescDate($a, $b) {
        return $a->getDate() < $b->getDate();
    }

    public static function cmpAscId($a, $b) {
        return $a->getId() > $b->getId();
    }

    public static function cmpDescId($a, $b) {
        return $a->getId() < $b->getId();
    }

    public static function cmpAscTotalProduct($a, $b) {
        return $a->getTotalProduct() > $b->getTotalProduct();
    }

    public static function cmpDescTotalProduct($a, $b) {
        return $a->getTotalProduct() < $b->getTotalProduct();
    }

    public static function cmpAscTotalQty($a, $b) {
        return $a->getTotalQty() > $b->getTotalQty();
    }

    public static function cmpDescTotalQty($a, $b) {
        return $a->getTotalQty() < $b->getTotalQty();
    }


    public function getMainButtonsHtml()
    {
        return '';
    }
    
    public function __construct() {        
        parent::__construct();
        $this->setId('warehouseGrid');
        $this->setDefaultSort('warehouse_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
        if (!$this->getFilterData())
            $this->setFilterData(new Varien_Object());
        $this->setUseAjax(true);
    }

     protected function _prepareCollection() {
        $adjustStockCollection = Mage::getModel('inventoryplus/adjuststock')->getCollection()
                                    ->addFieldToFilter('status', '1')
                                    ->setPageSize(10);
        $i = 0;
        $resource = Mage::getSingleton('core/resource');        
        $readConnection = $resource->getConnection('core_read');
        $datas = array();
        $collection = new Varien_Data_Collection();
        foreach( $adjustStockCollection as $adjustStock )
        {
            $items = 0;
            $qties = 0;
            $collection = Mage::getModel('inventoryplus/adjuststock_product')->getCollection();
            $collection->getCollectionByAsjuststockReport($adjustStock->getId());
            if ( $collection->getSize() )
            {
                foreach( $collection as $result )
                {
                    $items += $result['totalproduct'];
                    $qties += $result['totalqty'];
                }
            };
            $data[$i]['id'] = $adjustStock->getId();
            $data[$i]['total_product'] = $items;
            $data[$i]['total_qty'] = $qties;
            $data[$i]['date']= date('M d Y', strtotime($adjustStock->getCreatedAt()));
            $item = new Varien_Object($data[$i]);

            $collection->addItem($item);

            $i++;        
        }
        $this->setCollection($collection);       
      
        return parent::_prepareCollection();
    }    

    public function prepareExport() {
        $this->_prepareColumns();
        $this->_prepareCollection();
        return $this;
    }
    

    protected function _prepareColumns() {

        
        $this->addColumn('id', array(
            'header' => Mage::helper('inventoryreports')->__('ID'),
            'index' => 'id',
            'width' => '150px',
            'align' => 'left',
            'type' => 'number',
            'width' => '50px',
            'filter' => false
        ));
        $this->addColumn('date', array(
            'header' => Mage::helper('inventoryreports')->__('Date'),
            'index' => 'date',
            'width' => '150px',
            'align' => 'left',
            'type' => 'date',
            'filter' => false
        ));
      
        $this->addColumn('total_product', array(
            'header' => Mage::helper('inventoryreports')->__("Number of Products"),
            'align' => 'left',
            'index' => 'total_product',
            'type' => 'number',
            'filter' => false
        ));
        
        $this->addColumn('total_qty', array(
            'header' => Mage::helper('inventoryreports')->__('Total Qty of Products'),
            'align' => 'left',
            'index' => 'total_qty',
            'type' => 'number',
            'filter' => false
        ));

        



//        $this->addExportType('*/*/exportSalesOrdersCsv', Mage::helper('adminhtml')->__('CSV'));
//        $this->addExportType('*/*/exportSalesOrdersXml', Mage::helper('adminhtml')->__('Excel XML'));

        return parent::_prepareColumns();
    }


    protected function _setCollectionOrder($column) {
        $filterData = $this->getFilterData();
        $collection = $this->getCollection();
        if ($collection->getSize()) {
            $idColumn = $column->getId();
            $dir = $column->getDir();
            switch ($idColumn) {
                case 'date':
                    $this->_setCollectionOrderDate($collection,$dir);
                    break;
                case 'id':
                    $this->_setCollectionOrderId($collection,$dir);
                    break;
                case 'total_product':
                    $this->_setCollectionOrderTotalProduct($collection, $dir);
                    break;
                case 'total_qty':
                    $this->_setCollectionOrderTotalQty($collection, $dir);
                    break;
                default:
                    $filter = $column->getIndex();
                    if ($column->getFilterIndex())
                        $filter = $column->getFilterIndex();
                    if ($column->getDir() == 'asc') {
                        $collection->setOrder($filter, 'ASC');
                    } else {
                        $collection->setOrder($filter, 'DESC');
                    }
                    break;
            }

        }
    }

    protected function _setCollectionOrderDate($collection, $dir){
        $arr = array();
        foreach ($collection as $item) {
            $arr[] = $item;
        }
        if ( $dir == 'asc') {
            usort($arr, array($this, 'cmpAscDate'));
        } else {
            usort($arr, array($this, 'cmpDescDate'));
        }
        $temp = Mage::helper('inventoryreports')->_tempCollection(); // A blank collection
        $count = count($arr);
        for ($i = 0; $i < $count; $i++) {
            $temp->addItem($arr[$i]);
        }
        $this->setCollection($temp);
    }
    protected function _setCollectionOrderId($collection, $dir){
        foreach ($collection as $item) {
            $arr[] = $item;
        }
        if ($dir == 'asc') {
            $sorted = usort($arr, array($this, 'cmpAscId'));
        } else {
            $sorted = usort($arr, array($this, 'cmpDescId'));
        }
        $temp = Mage::helper('inventoryreports')->_tempCollection();
        $count = count($arr);
        for ($i = 0; $i < $count; $i++) {
            $temp->addItem($arr[$i]);
        }
        $this->setCollection($temp);
    }
    protected function _setCollectionOrderTotalProduct($collection, $dir){
        $arr = array();
        foreach ($collection as $item) {
            $arr[] = $item;
        }
        if ($dir == 'asc') {
            $sorted = usort($arr, array($this, 'cmpAscTotalProduct'));
        } else {
            $sorted = usort($arr, array($this, 'cmpDescTotalProduct'));
        }
        $temp = Mage::helper('inventoryreports')->_tempCollection(); // A blank collection
        $count = count($arr);
        for ($i = 0; $i < $count; $i++) {
            $temp->addItem($arr[$i]);
        }
        $this->setCollection($temp);
    }
    protected function _setCollectionOrderTotalQty($collection, $dir){
        $arr = array();
        foreach ($collection as $item) {
            $arr[] = $item;
        }
        if ($dir == 'asc') {
            $sorted = usort($arr, array($this, 'cmpAscTotalQty'));
        } else {
            $sorted = usort($arr, array($this, 'cmpDescTotalQty'));
        }
        $temp = Mage::helper('inventoryreports')->_tempCollection(); // A blank collection
        $count = count($arr);
        for ($i = 0; $i < $count; $i++) {
            $temp->addItem($arr[$i]);
        }
        $this->setCollection($temp);
    }
    public function getRowUrl($row) {
//        return $this->getUrl('*/*/view', array('id' => $row->getId()));
    }

    public function getGridUrl() {
        return $this->getUrl('*/*/adjustgrid',array('top_filter'=>$this->getRequest()->getParam('top_filter')));
    }

}