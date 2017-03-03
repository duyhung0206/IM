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
 * @package     Magestore_Inventorywarehouse
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Inventorywarehouse Helper
 * 
 * @category    Magestore
 * @package     Magestore_Inventorywarehouse
 * @author      Magestore Developer
 */
class Magestore_Inventorywarehouse_Helper_Warehouseshipment extends Magestore_Inventoryplus_Helper_Shipment
{

    protected $_observerOb;
    protected $_postParams;
    protected $_shipmentOb;
    protected $_orderOb;

    public function sendObject($observer, $postParams, $shipment, $order)
    {
        $this->_observerOb = $observer;
        $this->_postParams = $postParams;
        $this->_shipmentOb = $shipment;
        $this->_orderOb = $order;
    }

    /*
     * check product status is waiting for transfer
     * 
     * @return boolean
     */

    public function checkOrderItemWaittingFortransfer($orderItemId, $productId, $orderId, $warehouseId)
    {
        $shipmentTransferModel = Mage::getModel('inventoryplus/warehouse_shipment')
                ->getCollection()
                ->addFieldToFilter('item_id', $orderItemId)
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('order_id', $orderId)
                ->addFieldToFilter('warehouse_id', $warehouseId);
        $data = $shipmentTransferModel->setPageSize(1)->setCurPage(1)->getFirstItem()->getData();
        if ($data) {
            return true;
        } else {
            return false;
        }
    }

    public function getWarehouseNameByShipmentIdAndOrderitemId($shipmentId, $orderItemId)
    {
        $inventoryShipmentModel = Mage::getModel('inventoryplus/warehouse_shipment');
        $warehouse = $inventoryShipmentModel->getCollection()
                ->addFieldToFilter('shipment_id', $shipmentId)
                ->addFieldToFilter('item_id', $orderItemId)
                ->setPageSize(1)
                ->setCurPage(1)
                ->getFirstItem();
        if ($warehouseName = $warehouse->getWarehouseName())
            return $warehouseName;
    }

    /*
     * 	get warehouse ID to ship this order item
     * 	@param	var orderItem,var data, var qty_shipped
     * 	@return	warehouse_id
     */
    public function _getWarehouseIdToShip($orderItem, $data, $qtyShipped)
    {
        $warehouseId = null;
        if (isset($data['warehouse-shipment']['items'][$orderItem->getItemId()]) && $data['warehouse-shipment']['items'][$orderItem->getItemId()]) {
            $warehouseId = $data['warehouse-shipment']['items'][$orderItem->getItemId()];
        } elseif ($orderItem->getParentItemId() && isset($data['warehouse-shipment']['items'][$orderItem->getParentItemId()])) {
            $warehouseId = $data['warehouse-shipment']['items'][$orderItem->getParentItemId()];
        } else {
            /* integrate with WebPOS 2.0 or later	 */
            if (Mage::helper('core')->isModuleEnabled('Magestore_Webpos') && Mage::helper('core')->isModuleEnabled('Magestore_Inventorywebpos')) {
                if (Mage::helper('inventoryplus')->isWebPOS20Active()) {
                    $warehouseId = Mage::helper('inventorywebpos')->_getCurrentWarehouseId();
                }
            }

            /* Integrate with XPos */
            if (Mage::helper('core')->isModuleEnabled('SM_XPos') && Mage::helper('core')->isModuleEnabled('Magestore_Inventoryxpos')) {
                $warehouseId = Mage::helper('inventoryxpos/warehouse')->getCurrentWarehouseId();
            }

            /**
             * Integrate with M2ePro
             */
            if (Mage::helper('inventoryplus/integration')->isM2eProActive()) {
                $warehouseId = Mage::helper('inventorym2epro/warehouse')->getWarehouseForM2ePro();
            }

            if (!$warehouseId) {
                $warehouseId = $this->selectWarehouseOrderToShip($orderItem->getProductId(), $orderItem->getOrderId(), $qtyShipped);
            }

            if (!$warehouseId) {
                $warehouseId = $this->selectWarehouseDefaultToShip($orderItem->getProductId(), $qtyShipped);
            }
        }
        return $warehouseId;
    }

    /**
     * 
     * @param int $product_id
     * @param int $order_id
     * @param float $qty
     * @return int|null
     */
    protected function selectWarehouseOrderToShip($product_id, $order_id, $qty)
    {
        $warehouseOrderItem = Mage::getModel('inventoryplus/warehouse_order')
                ->getCollection()
                ->addFieldToFilter('product_id', $product_id)
                ->addFieldToFilter('order_id', $order_id)
                ->setPageSize(1)
                ->setCurPage(1)
                ->getFirstItem();
        if($warehouseOrderItem->getWarehouseId()) {
            $warehouseProduct = Mage::getModel('inventoryplus/warehouse_product')
                    ->getCollection()
                    ->addFieldToFilter('product_id', $product_id)
                    ->addFieldToFilter('warehouse_id', $warehouseOrderItem->getWarehouseId())
                    ->setPageSize(1)
                    ->setCurPage(1)
                    ->getFirstItem();
            if($warehouseProduct->getTotalQty() > floatval($qty)) {
                return $warehouseOrderItem->getWarehouseId();
            }
        }
        return null;
    }

    /**
     * 
     * @param int $product_id
     * @param float $qty
     * @return int|null
     */
    protected function selectWarehouseDefaultToShip($product_id, $qty)
    {
        $warehouseProduct = Mage::getModel('inventoryplus/warehouse_product')
                ->getCollection()
                ->addFieldToFilter('product_id', $product_id)
                ->addFieldToFilter('total_qty', array('gteq' => $qty))
                ->setPageSize(1)
                ->setCurPage(1)
                ->getFirstItem();
        if(!$warehouseId = $warehouseProduct->getWarehouseId()) {
            $warehouseId = Mage::helper('inventoryplus/warehouse')->getPrimaryWarehouse()->getId();
        }
        return $warehouseId;
    }

    /*
     * 	prepare and save warehouse_order model
     * 	@param	var warehouse_id,var product_id, var qtyShipped, model warehouseProduct
     * 	@return	object
     */
    public function _saveModelWarehouseOrder($warehouse_id, $product_id, $qtyShipped, $warehouseProduct)
    {
        $warehouseOr = Mage::getModel('inventoryplus/warehouse_order')->getCollection()
                ->addFieldToFilter('order_id', $this->_orderOb->getId())
                ->addFieldToFilter('product_id', $product_id)
                ->setPageSize(1)
                ->setCurPage(1)
                ->getFirstItem();
        if (!$warehouseOr->getId())
            return;
        if ($warehouseOr->getWarehouseId() != $warehouse_id) {
            /* Ship from other warehouse (not in warehouse_order) */
            $newQtyAvailable = $warehouseProduct->getAvailableQty() - $qtyShipped;
            $warehouseProduct->setAvailableQty($newQtyAvailable);
            $warehouseProduct->save();
            /* Tru from warehouse dc select thi phai tra lai cho warehouse da order */
            $warehouseOrderedProduct = Mage::getModel('inventoryplus/warehouse_product')->getCollection()
                    ->addFieldToFilter('warehouse_id', $warehouseOr->getWarehouseId())
                    ->addFieldToFilter('product_id', $product_id)
                    ->setPageSize(1)
                    ->setCurPage(1)
                    ->getFirstItem();
            $newQtyAvailWarehouseOrdered = $warehouseOrderedProduct->getAvailableQty() + $qtyShipped;
            $warehouseOrderedProduct->setAvailableQty($newQtyAvailWarehouseOrdered)
                    ->save();
        }
        $OnHoldQty = $warehouseOr->getQty() - $qtyShipped;
        if ($OnHoldQty >= 0) {
            $warehouseOr->setQty($OnHoldQty)
                    ->save();
        } else {
            $warehouseOr->setQty(0)
                    ->save();
        }
        return $warehouseOr;
    }

    /*
     * 	prepare and save warehouse_transaction_product model
     * 	@param	var transactionId,var product_id, var transactionProduct
     * 	@return	null
     */

    public function _saveModelTransactionProduct($transactionId, $productId, $transactionProduct)
    {
        try {
            Mage::getModel('inventorywarehouse/transaction_product')
                    ->setWarehouseTransactionId($transactionId)
                    ->setProductId($productId)
                    ->setProductSku($transactionProduct['product_sku'])
                    ->setProductName($transactionProduct['product_name'])
                    ->setQty(-$transactionProduct['qty_shipped'])
                    ->save();
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'inventory_management.log');
        }
    }

    /*
     * 	prepare and save warehouse_transaction model
     * 	@param	var transactionSendData
     * 	@return	object
     */

    public function _saveModelTransaction($transactionSendData)
    {
        $transactionSendModel = Mage::getModel('inventorywarehouse/transaction');
        $transactionSendModel->addData($transactionSendData);
        try {
            $transactionSendModel->save();
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'inventory_management.log');
        }
        return $transactionSendModel;
    }

}
