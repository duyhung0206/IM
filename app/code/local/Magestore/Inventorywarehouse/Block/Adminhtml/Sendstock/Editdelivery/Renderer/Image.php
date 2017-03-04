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

class Magestore_Inventorywarehouse_Block_Adminhtml_Sendstock_Editdelivery_Renderer_Image extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    
    /**
     * 
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        if($row->getProductId()){
            $product = Mage::getModel('catalog/product')->load($row->getProductId());
            $val = Mage::helper('catalog/image')->init($product, 'image')->resize(90);
            $out = "<img src=". $val ." width='90px'/>";
        }else{
            $val = Mage::helper('catalog/image')->init($row, 'image')->resize(90);
            $out = "<img src=". $val ." width='90px'/>";
        }

        return $out;
    }


}

