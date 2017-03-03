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
 * @package     Magestore_Inventoryplus
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
class Magestore_Inventoryreports_Block_Adminhtml_Reportcontent_Reportbybestseller_Grid
    extends Magestore_Inventoryreports_Block_Adminhtml_Reportcontent_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('bestsellergrid');
        $this->setDefaultSort('ordered_qty');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
    }

    protected function _prepareCollection() {
        $requestData = $this->getRequestData();
        $collection =  Mage::helper('inventoryreports/bestseller')->getBestSellerProductCollection($requestData);
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn('order_items_name', array(
            'header' => Mage::helper('inventoryreports')->__('Product Name'),
            'index' => 'order_items_name',
            'type' => 'text',
            'filter_condition_callback' => array($this, '_filterTextCallback'),
        ));
        $this->addColumn('sku', array(
            'header' => Mage::helper('inventoryreports')->__('Product Sku'),
            'index' => 'sku',
            'type' => 'text',
            'filter_condition_callback' => array($this, '_filterTextCallback'),
        ));

        $this->addColumn('ordered_qty', array(
            'header' => Mage::helper('inventoryreports')->__('Sale Qty'),
            'align' => 'right',
            'index' => 'ordered_qty',
            'type' => 'number',
            'width' => '50px',
            'filter_condition_callback' => array($this, '_filterNumberCallback'),
        ));
        return parent::_prepareColumns();
    }

    public function getGridUrl() {
        return $this->getUrl('*/*/bestsellergrid', array('type_id' => 'bestseller', 'top_filter' => $this->getRequest()->getParam('top_filter')));
    }

    public function getRowUrl($row) {
        return false;
    }

    protected function _getRealFieldFromAlias($alias) {
        $field = null;
        switch ($alias) {
            case 'sku':
                $field = 'IFNULL(catalogproduct.sku, 0)';
                break;
            case 'order_items_name':
                $field = 'IFNULL(order_items.name, 0)';
                break;
//            case 'sku':
//                $field = 'IFNULL(`order_items`.sku, 0)';
//                break;
            default :
                $field = $alias;
        }
        if($field != $alias){
            $field = new Zend_Db_Expr($field);
        }
        return $field;
    }
}
