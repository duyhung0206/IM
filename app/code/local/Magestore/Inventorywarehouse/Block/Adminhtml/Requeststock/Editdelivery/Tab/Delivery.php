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

/**
 * Inventorypurchasing Adminhtml Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventorypurchasing
 * @author      Magestore Developer
 */
class Magestore_Inventorywarehouse_Block_Adminhtml_Requeststock_Editdelivery_Tab_Delivery
    extends Mage_Adminhtml_Block_Widget_Grid
    implements Magestore_Inventoryplus_Block_Adminhtml_Barcode_Scan_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('productGrid');
        $this->setUseAjax(true); // Using ajax grid is important
        $this->setDefaultSort('warehouse_requeststock_product_id');
//        $this->setDefaultFilter(array('in_products'=>1)); // By default we have added a filter for the rows, that in_products value to be 1
        $this->setSaveParametersInSession(false);
    }


    protected function _prepareCollection() {
        $requeststock_id = $this->getRequest()->getParam('requeststock_id');
        $requestockProducts = Mage::getModel('inventorywarehouse/requeststock_product')->getCollection()
            ->addFieldToFilter('warehouse_requeststock_id', $requeststock_id);
        $productIds = array();
        foreach ($requestockProducts as $requestockProduct) {
            if ($requestockProduct->getTotalDelivery() < $requestockProduct->getQty())
                $productIds[] = $requestockProduct->getProductId();
        }

        $collection = Mage::getResourceModel('catalog/product_collection')
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', array('in' => $productIds));
        $collection->getSelect()
            ->joinLeft(array('requeststockproduct' => $collection->getTable('erp_inventory_warehouse_requeststock_product')),
                'e.entity_id = requeststockproduct.product_id
							and requeststockproduct.warehouse_requeststock_id IN (' . $this->getRequest()->getParam('requeststock_id') . ')'
                , array('total_request' => 'requeststockproduct.qty',
                        'total_delivery' => 'requeststockproduct.total_delivery'));
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn('in_products', array(
            'header_css_class'  => 'a-center',
            'type'              => 'checkbox',
            'name'              => 'product_delivery',
            'values'            => $this->_getSelectedProducts(),
            'align'             => 'center',
            'width'             => '60',
            'index'             => 'entity_id'
        ));
        $this->addColumn('entity_id', array(
            'header' => Mage::helper('inventorywarehouse')->__('ID'),
            'align' => 'left',
            'width' => '60',
            'index' => 'entity_id',
        ));
        $this->addColumn('name', array(
            'header' => Mage::helper('inventorywarehouse')->__('Name'),
            'align' => 'left',
            'index' => 'name',
        ));

        $this->addColumn('sku', array(
            'header' => Mage::helper('inventorywarehouse')->__('SKU'),
            'width' => '80px',
            'index' => 'sku'
        ));
        $this->addColumn('image', array(
            'header' => Mage::helper('inventorywarehouse')->__('Image'),
            'align' => 'left',
            'width' => '90',
            'index' => 'image',
            'renderer' => 'inventorywarehouse/adminhtml_requeststock_editdelivery_renderer_image'
        ));
        $this->addColumn('total_request', array(
            'header' => Mage::helper('inventorywarehouse')->__('Total Qty Request'),
            'name' => 'qty',
            'type' => 'number',
            'index' => 'total_request',
            'filter' => false,
        ));
        $this->addColumn('total_delivery', array(
            'header' => Mage::helper('inventorywarehouse')->__('Total Qty Delivery'),
            'name' => 'total_delivery',
            'type' => 'number',
            'index' => 'total_delivery',
            'filter' => false,
        ));
        $this->addColumn('qty_delivery', array(
            'header' => Mage::helper('inventorywarehouse')->__('Qty Delivery'),
            'type' => 'number',
            'filter' => false,
            'editable' => true,
            'edit_only' => true,
            'align' => 'right',
            'sortable' => false,
            'renderer' => 'inventorywarehouse/adminhtml_requeststock_editdelivery_renderer_qtydelivery'
        ));

    }

    protected function _getSelectedProducts()   // Used in grid to return selected customers values.
    {
        $products = array_keys($this->getSelectedProducts());
        return $products;
    }

    public function getSelectedProducts()
    {
        $requeststock_id = $this->getRequest()->getParam('requeststock_id');
        $productIds = array();
        $requeststock_products = Mage::getModel('inventorywarehouse/requeststock_product')->getCollection()
            ->addFieldToFilter('warehouse_requeststock_id', $requeststock_id);
        foreach ($requeststock_products as $requeststock_product){
            $requeststockdelivery = Mage::getModel('inventorywarehouse/requeststockdelivery')->getCollection()
                ->addFieldToFilter('warehouse_requeststock_id', $requeststock_id)
                ->addFieldToFilter('product_id', $requeststock_product->getProductId());
            $total_delivery = $requeststockdelivery->getSelect()
                ->columns('SUM(qty_delivery) as qty_delivery')
                ->group('qty_delivery');
            if(count($total_delivery) || $total_delivery->getQtyDelivery() == 0 || !isset($total_delivery)){

            }else{
                $productIds[$requeststock_product->getProductId()] = 0;
            }
        }
        return $productIds;
    }

    protected function _addColumnFilterToCollection($column)
    {
        // Set custom filter for in product flag
        if ($column->getId() == 'in_products') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', array('in'=>$productIds));
            } else {
                if($productIds) {
                    $this->getCollection()->addFieldToFilter('entity_id', array('nin'=>$productIds));
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    public function getGridUrl() {
        return $this->getUrl('*/*/preparedelivery', array(
            '_current' => true,
            'id' => $this->getRequest()->getParam('id'),
            'store' => $this->getRequest()->getParam('store')
        ));
    }

    public function getRowUrl($row) {
        return false;
    }

    public function getRowClass($row) {
        return 'row-'. $row->getEntityId();
    }
}
