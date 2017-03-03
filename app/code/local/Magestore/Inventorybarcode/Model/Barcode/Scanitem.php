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
 * Inventorybarcode Model
 * 
 * @category    Magestore
 * @package     Magestore_Inventorybarcode
 * @author      Magestore Developer
 */
class Magestore_Inventorybarcode_Model_Barcode_Scanitem extends Mage_Core_Model_Abstract {

    const STATUS_PENDING = 0;
    const STATUS_COMPLETE = 1;

    public function _construct() {
        parent::_construct();
        $this->_init('inventorybarcode/barcode_scanitem');
    }

    /**
     * Add new scanned item
     * 
     * @param int $productId
     * @param string $action
     * @param float $qty
     * @return \Magestore_Inventorybarcode_Model_Barcode_Scanitem
     */
    public function addItem($productId, $action, $qty = 1) {
        $currentAdminId = Mage::getSingleton('admin/session')->getUser()->getUserId();
        $item = $this->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('user_id', $currentAdminId)
                ->addFieldToFilter('action', $action)
                ->addFieldToFilter('is_finished', self::STATUS_PENDING)
                ->getFirstItem();

        $item->setData('product_id', $productId)
                ->setData('user_id', $currentAdminId)
                ->setData('action', $action)
                ->setData('is_finished', self::STATUS_PENDING)
                ->setData('scan_qty', $item->getScanQty() + $qty)
                ->setData('last_scanned_at', now())
                ->save();

        return $item;
    }

    /**
     * Get scanned items
     * 
     * @param array $productIds
     * @param string $action
     */
    public function getItems($productIds, $action) {
        $currentAdminId = Mage::getSingleton('admin/session')->getUser()->getUserId();
        $items = $this->getCollection();
        if (count($productIds)) {
            $items->addFieldToFilter('product_id', array('in' => $productIds));
        }
        $items->addFieldToFilter('user_id', $currentAdminId)
                ->addFieldToFilter('action', $action)
                ->addFieldToFilter('is_finished', self::STATUS_PENDING);

        return $items;
    }
    
    /**
     * Reset scan data
     * 
     * @param string $action
     * @return \Magestore_Inventorybarcode_Model_Barcode_Scanitem
     */
    public function reset($action) {
        $items = $this->getItems(array(), $action);
        if(count($items)){
            foreach($items as $item) {
                $item->delete();
            }
        }
        return $this;
    }

}
