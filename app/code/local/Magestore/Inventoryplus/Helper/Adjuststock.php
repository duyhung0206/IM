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
 * Inventory Helper
 * 
 * @category    Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventoryplus_Helper_Adjuststock extends Mage_Core_Helper_Abstract {

    /**
     * Check permission to adjust stock.
     * 
     * @return boolean
     */
    public function getWarehouseByAdmin() {
        $adminId = Mage::getSingleton('admin/session')->getUser()->getId();
        $warehouseIds = array();
        $collection = Mage::getModel('inventoryplus/warehouse_permission')->getCollection()
                ->addFieldToFilter('admin_id', $adminId)
                ->addFieldToFilter('can_adjust', 1);
        foreach ($collection as $assignment) {
            $warehouseIds[] = $assignment->getWarehouseId();
        }
        $warehouseCollection = Mage::getModel('inventoryplus/warehouse')->getCollection()
                ->addFieldToFilter('warehouse_id', array('in' => $warehouseIds));
        if ($warehouseCollection->getSize()) {
            return true;
        }
        return false;
    }

    /**
     * Import data for product grid
     * 
     * @return null
     */
    public function importProduct($data) {
        if (count($data)) {
            Mage::getModel('admin/session')->setData('adjuststock_product_import', $data);
        }
    }

    /**
     * Create a new adjust stock
     * 
     * @param type $model
     * @param type $warehouseId
     * @param type $warehouse
     * @param type $data
     * @param type $admin
     */
    public function createAdjuststock($model, $warehouseId, $warehouse, $data, $admin) {
        $model->setWarehouseId($warehouseId)
                ->setWarehouseName($warehouse->getWarehouseName())
                ->setCreatedAt(now())
                ->setReason($data['reason'])
                ->setData('created_by', $admin)
                ->setStatus(0)
        ;
        $model->save();
    }

    /**
     * Confirm an adjust stock
     * 
     * @param type $model
     * @param type $data
     * @param type $admin
     */
    public function confirmAdjuststock($model, $data, $admin) {
        $model->setData('reason', $data['reason'])
                ->setData('confirmed_by', $admin)
                ->setData('confirmed_at', now())
                ->setStatus(1);
        $model->save();
    }

    /**
     * Cancel an adjust stock
     * 
     * @param type $model
     */
    public function cancelAdjuststock($model) {
        $model->setStatus(2);
        $model->save();
        Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('inventoryplus')->__('The stock adjustment has been successfully canceled.')
        );
    }

    /**
     * Prepare adjust stock data
     * 
     * @param string $adjustStockString
     * @return array
     */
    protected function _prepareAdjustStockData($adjustStockString) {
        $adjuststockProducts = array();
        $adjuststockProductsExplodes = explode('&', urldecode($adjustStockString));
        if (count($adjuststockProductsExplodes) <= 900) {
            Mage::helper('inventoryplus')->parseStr(urldecode($adjustStockString), $adjuststockProducts);
        } else {
            foreach ($adjuststockProductsExplodes as $adjuststockProductsExplode) {
                $adjuststockProduct = '';
                Mage::helper('inventoryplus')->parseStr($adjuststockProductsExplode, $adjuststockProduct);
                $adjuststockProducts = $adjuststockProducts + $adjuststockProduct;
            }
        }
        return $adjuststockProducts;
    }

}
