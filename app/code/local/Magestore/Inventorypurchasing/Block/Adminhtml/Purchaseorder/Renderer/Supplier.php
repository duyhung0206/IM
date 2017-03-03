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
 * Inventory Supplier Grid Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventorypurchasing_Block_Adminhtml_Purchaseorder_Renderer_Supplier extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
        $supplier_id = $row->getSupplierId();
        $contentCSV = '';
        $content = '';
		$url = Mage::helper('adminhtml')->getUrl('adminhtml/inpu_supplier/edit',array('id'=>$supplier_id));
		$name = $row->getSupplierName();
		$content .= "<a href=".$url.">$name<a/>"."<br/>";
		$contentCSV = $name;

        if(in_array(Mage::app()->getRequest()->getActionName(),array('exportCsv','exportXml')))
            return $contentCSV;
        return '<label>'.$content.'</label>';      
    }
    
    public function renderExport(Varien_Object $row){
        return $row->getSupplierName();;
    }

}
