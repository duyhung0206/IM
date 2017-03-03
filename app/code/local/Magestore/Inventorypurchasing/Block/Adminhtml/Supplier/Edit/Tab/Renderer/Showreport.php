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
 * @package     Magestore_Inventory
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Supplier Edit Form Content Tab Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventory_Block_Adminhtml_Supplier_Edit_Tab_Renderer_Showreport
	extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row) 
    {
        $productId = $row->getEntityId();
        $supplierId = Mage::app()->getRequest()->getParam('id');
        $supplier = Mage::helper('inventorypurchasing/supplier')->getAllSupplierName();
        if(count($supplier) && !$supplierId){
            $model = Mage::getModel('inventorypurchasing/supplier');
            $firstItem = $model->getCollection()->setPageSize(1)->setCurPage(1)->getFirstItem();
            $supplierId = $firstItem->getSupplierId();
        }
        return '<p style="text-align:center"><a name="url" href="# return false;" onclick="showreport('.$productId.','.$supplierId.'); return false;">'.Mage::helper('inventorypurchasing')->__('View').'</a></p>';       
    }
}