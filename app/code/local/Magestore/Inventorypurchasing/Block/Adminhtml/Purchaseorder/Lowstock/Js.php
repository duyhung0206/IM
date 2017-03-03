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
class Magestore_Inventorypurchasing_Block_Adminhtml_Purchaseorder_Lowstock_Js extends Mage_Adminhtml_Block_Widget_Grid {

    protected function _prepareLayout() {
        parent::_prepareLayout();
        $this->setTemplate('inventorypurchasing/purchaseorder/lowstock/js.phtml');
    }

    public function getCreatePOUrl() {
        return $this->getUrl('adminhtml/inpu_lowstock/createpo', array('_secure' => true));
    }

    public function getChangeSupplierUrl() {
        return $this->getUrl('adminhtml/inpu_lowstock/changesupplier', array('_secure' => true));
    }
    
    public function getSelectSupplierMessage() {
        return $this->__('Please select Supplier before creating Purchase Order.');
    }
    
    public function getGridObject() {
        return $this->getLayout()->getBlock($this->getGridBlockName())->getId() .'JsObject';
    }
}
