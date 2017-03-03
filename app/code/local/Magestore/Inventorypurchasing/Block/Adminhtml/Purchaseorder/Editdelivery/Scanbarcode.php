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
class Magestore_Inventorypurchasing_Block_Adminhtml_Purchaseorder_Editdelivery_Scanbarcode extends Magestore_Inventoryplus_Block_Adminhtml_Barcode_Scan_Form {

    const SCAN_ACTION = 'delivery_po';

    /**
     * 
     * @return string
     */
    public function getScanActionName() {
        return self::SCAN_ACTION . '_' . $this->getRequest()->getParam('purchaseorder_id');
    }

    /*
     * 
     * @return string
     */

    public function getQtyInput() {
        $warehousIds = $this->getRequest()->getParam('warehouse_ids');
        $warehousIds = explode(',', $warehousIds);
        if (isset($warehousIds[0])) {
            return 'warehouse_' . $warehousIds[0];
        }
        return null;
    }

}
