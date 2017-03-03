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
class Magestore_Inventorybarcode_Block_Adminhtml_Printbarcode_Renderer_ShowAttribtule extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    /**
     * Renders grid column
     *
     * @param   Varien_Object $row
     * @return  string
     */
    public function render(Varien_Object $row)
    {
        $option= array();
        $attribute_show='';
        $templateId=$row->getId();
        $collection = Mage::getModel('inventorybarcode/barcodetemplate')->load($templateId);
        if($collection->getData('productname_show')==1){
            array_push($option, 'Product name');
        }
        if($collection->getData('sku_show')==2){
            array_push($option, 'Sku');
        }
        if($collection->getData('price_show')==3){
            array_push($option, 'Price');
        }
        $attribute_show= implode(', ', $option);
        return $attribute_show;
    }
}
