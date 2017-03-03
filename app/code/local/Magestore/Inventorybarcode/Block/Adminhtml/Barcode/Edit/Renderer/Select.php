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
class Magestore_Inventorybarcode_Block_Adminhtml_Barcode_Edit_Renderer_Select extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(Varien_Object $row)
    {
        $name = $this->getColumn()->getName() ? $this->getColumn()->getName() : $this->getColumn()->getId();
        $html = '<select name="' . $this->escapeHtml($name) . '" ' . $this->getColumn()->getValidateClass() . 'onchange="changeProductQty('.$row->getEntityId().');" id="warehouseID_'.$row->getEntityId().'"> ';
        $value = $row->getData($this->getColumn()->getIndex());
        if ($barcodeProducts = Mage::getModel('admin/session')->getData('barcode_product_import')) {
            $fieldIds = explode('_', $this->getColumn()->getId());
            foreach($fieldIds as $id => $value){
                if($id == 1)
                    $_field = $value;
                if($id > 1)
                    $_field .= '_'. $value;
            }
            foreach($barcodeProducts as $barcodeProduct){
                if($barcodeProduct['PRODUCT_ID']==$row->getId()){
                    if(isset($barcodeProduct['BARCODE_STATUS'])){
                        $value = $barcodeProduct['BARCODE_STATUS'];
                    }
                }
            }

        }

        foreach ($this->getColumn()->getOptions() as $val => $label){
            $selected = ( ($val == $value && (!is_null($value))) ? ' selected="selected"' : '' );
            $html .= '<option  value="' . $this->escapeHtml($val) . '"' . $selected . '>';
            $html .= $this->escapeHtml($label) . '</option>';
        }
        $html.='</select>';
        return $html;
    }
}

