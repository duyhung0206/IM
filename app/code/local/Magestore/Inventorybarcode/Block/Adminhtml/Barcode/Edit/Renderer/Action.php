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
 * Inventorybarcode Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventorybarcode
 * @author      Magestore Developer
 */
class Magestore_Inventorybarcode_Block_Adminhtml_Barcode_Edit_Renderer_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {


    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(Varien_Object $row)
    {
        $html = '';
        if($this->helper('inventorybarcode')->isMultipleBarcode()) {
            $html .= '<a href="'. $this->getUrl('*/*/edit', array('id'=>$row->getBarcodeId())) .'">'. $this->__('View').'</a>';
            $html .= ' | <a onclick="window.open(\''. $this->getUrl('*/inb_printbarcode/selecttemplate', array('barcode'=>$row->getBarcodeId())) .'\',\'_blank\', \'scrollbars=yes, resizable=yes, width=750, height=500, left=80, menubar=yes\');   " href="javascript:void(0);">'. $this->__('Print').'</a>';
        } else {
            $html .= '<a onclick="window.open(\''. $this->getUrl('*/inb_printbarcode/selecttemplate', array('barcode'=>$row->getEntityId())) .'\',\'_blank\', \'scrollbars=yes, resizable=yes, width=750, height=500, left=80, menubar=yes\');   " href="javascript:void(0);">'. $this->__('Print').'</a>';
        }
        return $html;
    }    
}

