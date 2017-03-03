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

class Magestore_Inventorypurchasing_Block_Adminhtml_Purchaseorder_Lowstock_Header extends Mage_Adminhtml_Block_Widget_Grid {
    
    protected function _prepareLayout() {
        parent::_prepareLayout();
        $this->setTemplate('inventorypurchasing/purchaseorder/lowstock/header.phtml');
    }
    
    public function getCreatePOUrl() {
        
    }
    
    public function getSupplierList() {
        if(!$this->hasData('supplier_list')){
            $list = Mage::getModel('inventorypurchasing/supplier')->getCollection();
            $this->setData('supplier_list', $list);
        }
        return $this->getData('supplier_list');
    }
    
    public function getWarehouseList() {
        if(!$this->hasData('warehouse_list')) {
            $list = Mage::helper('inventoryplus/warehouse')->getAllWarehouseName();
            $this->setData('warehouse_list', $list);
        }
        return $this->getData('warehouse_list');
    }

    public function getCurrentSupplierIds() {
        $supplierIds = $this->helper('inventorypurchasing/lowstock')->getCurrentSupplierIds();
        return $supplierIds;
    }
    
    
    public function getCurrentWarehouseIds() {
        $warehouseIds = $this->helper('inventorypurchasing/lowstock')->getCurrentWarehouseIds();
        return $warehouseIds;
    }    
    
    public function getFilterUrl() {
        return $this->getUrl('adminhtml/inpu_lowstock',  array(
                '_current' => true,
                '_secure' => true,
            ));
    }
    
   /**
     * 
     * @return \Magestore_Inventorysupplyneeds_Model_Draftpo
     */
    public function getDraftPO(){
        if(!$this->hasData('draft_purchaseorder')){
            $draftPO = $this->helper('inventorypurchasing/draftpo')->getDraftPO();
            $this->setData('draft_purchaseorder', $draftPO);
        }
        return $this->getData('draft_purchaseorder');
    }
    
    /**
     * 
     * @return boolean
     */
    public function hasDraftPO(){
        if($this->getDraftPO()->getId())
            return true;
        return false;
    }
    
    /**
     * 
     * @return string
     */
    public function getDraftPOUrl() {
        if(!$this->hasDraftPO())
            return null;
        return $this->getUrl('adminhtml/inpu_draftpo/view', array('id'=>$this->getDraftPO()->getId()));
    }    
    
}