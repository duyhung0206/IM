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
 * Inventory Resource Model
 * 
 * @category    Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventoryplus_Model_Mysql4_Warehouse_Order extends Mage_Core_Model_Mysql4_Abstract {

    public function _construct() {
        $this->_init('inventoryplus/warehouse_order', 'warehouse_order_id');
    }

    public function getOnHoldQty($productId, $warehouseId = null) {
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
                ->from($this->getMainTable(), 'SUM(qty)')
                ->where('product_id = :product_id');
        if ($warehouseId) {
            $select->where('warehouse_id = :warehouse_id');
            $bind = array(':product_id' => $productId, ':warehouse_id' => $warehouseId);
        } else {
            $bind = array(':product_id' => $productId);
        }
        return floatval($adapter->fetchOne($select, $bind));
    }

}
