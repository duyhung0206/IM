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

class Magestore_Inventorywarehouse_Block_Adminhtml_Sendstock_Editdelivery_Renderer_Qtydelivery extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    
    /**
     * 
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row)
    {
        $qty = $this->getQtyDelivery($row);
        $str = Mage::helper('inventorywarehouse')->__('Received: ').  abs($row->getTotalDelivery()) .'/'. abs($row->getTotalSend());
        $str .= '<input type="text" class="input-text '
            . $this->getColumn()->getValidateClass()
            . '" name="' . $this->getColumn()->getId()
            . '" value="' . $qty . '"/>';
        return $str;
    }

    /**
     *
     * @param Varien_Object $row
     * @return int|float
     */
    public function getQtyDelivery($row) {
        if(!Mage::registry('get_qty_delivery'. $row->getId())) {
            Mage::register('get_qty_delivery'. $row->getId(), true);
            return floatval($row->getData('qty_delivery'));
        }
        return 0;
    }

}

