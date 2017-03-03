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
 * Warehouse Edit Form Content Tab Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventorypurchasing_Block_Adminhtml_Purchaseorder_Returnorder_Renderer_Warehouse extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) {
        $productId = $row->getId();
        $purchaseorderId = $this->getRequest()->getParam('purchaseorder_id');
        $warehouse_ids = explode("warehouse_", $this->getColumn()->getData('name'));
        $warehouse_id = $warehouse_ids[1];
        $poProductWH = Mage::getResourceModel('inventorypurchasing/purchaseorder_productwarehouse');
        $result = $poProductWH->getQtyData($purchaseorderId, $productId, $warehouse_id);

        $qty = 0;
        if ($result['qty_returned'])
            $qty = $result['qty_returned'];
        $str = Mage::helper('inventorypurchasing')->__('Returned: ') . $qty;

        $qty = 0;
        if (isset($result['qty_received'])) {
            $qty = $result['qty_received'] - $result['qty_returned'];
        }
        if ($qty > 0) {
            $str .= '<input type="text" class="input-text '
                    . $this->getColumn()->getValidateClass()
                    . '" name="' . $this->getColumn()->getId()
                    . '" value="' . $qty . '"/>';
        }
        return $str;
    }

}
