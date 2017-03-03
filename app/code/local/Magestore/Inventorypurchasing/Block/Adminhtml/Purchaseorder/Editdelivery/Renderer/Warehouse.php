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

class Magestore_Inventorypurchasing_Block_Adminhtml_Purchaseorder_Editdelivery_Renderer_Warehouse extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    
    /**
     * 
     * @param Varien_Object $row
     * @return string
     */
    public function render(Varien_Object $row) {       
        $productId = $row->getId();
        $purchaseorderId = $this->getRequest()->getParam('purchaseorder_id');
        $warehouse_ids = explode("warehouse_", $this->getColumn()->getData('name'));
        $warehouse_id = $warehouse_ids[1];
        $poProductWH = Mage::getResourceModel('inventorypurchasing/purchaseorder_productwarehouse');
        $result = $poProductWH->getQtyData($purchaseorderId, $productId, $warehouse_id);

        $receivedQty = isset($result['qty_received']) ? $result['qty_received'] : 0;
        $str = Mage::helper('inventorypurchasing')->__('Received: '). $receivedQty .'/'. $result['qty_order'];
        
        $qty = $this->getScanQty($row); 
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
    public function getScanQty($row) {
        if(!Mage::registry('get_scan_qty'. $row->getId())) {
            Mage::register('get_scan_qty'. $row->getId(), true);
            return floatval($row->getData('scan_qty'));
        }
        return 0;
    }
}

