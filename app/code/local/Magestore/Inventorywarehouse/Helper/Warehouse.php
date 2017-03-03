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
class Magestore_Inventorywarehouse_Helper_Warehouse extends Mage_Core_Helper_Abstract
{
    /**
     * define the ways to select order warehouse
     */
    const LARGEST_QTY_WAREHOUSE = 1;
    const SMALLEST_QTY_WAREHOUSE = 2;
    const CLOSEST_WAREHOUSE = 3;
    const ASSOCIATED_STORE_WAREHOUSE = 4;
    
    
    /*
     * get available qty of the first warehouse for shipment
     */

    public function checkTheFirstWarehouseAvailableProduct($productId, $minQty, $orderId)
    {
        $warehouseOrder = Mage::getModel('inventoryplus/warehouse_order')->getCollection()
                ->addFieldToFilter('order_id', $orderId)
                ->addFieldToFilter('product_id', $productId);
        $firstWarehouse = $warehouseOrder->setPageSize(1)->setCurPage(1)->getFirstItem()->getWarehouseId();

        $warehouseProductModel = Mage::getModel('inventoryplus/warehouse_product')->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('warehouse_id', $firstWarehouse)
                ->addFieldToFilter('total_qty', array('gteq' => $minQty))
                ->setOrder('total_qty', 'DESC');

        if ($warehouseProductModel->setPageSize(1)->setCurPage(1)->getFirstItem()->getData()) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * get the firrst warehouse ha most this product
     */

    public function getFirstWarehouseHaveMostOfAProduct($productId)
    {

        $warehouseProductModel = Mage::getModel('inventoryplus/warehouse_product')->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->setOrder('total_qty', 'DESC');
        if (count($warehouseProductModel) > 1) {
            $warehouseProductModel = Mage::getModel('inventoryplus/warehouse_product')->getCollection()
                    ->addFieldToFilter('product_id', $productId)
                    ->setOrder('total_qty', 'DESC');
        }
        if ($warehouseProductModel->setPageSize(1)->setCurPage(1)->getFirstItem()->getData()) {
            $warehouseId = $warehouseProductModel->setPageSize(1)->setCurPage(1)->getFirstItem()->getWarehouseId();
        } else {
            $allWarehouse = $this->getAllWarehouseNameEnable();
            foreach ($allWarehouse as $warehouseIdKey => $warehouseNameValue) {
                if ($warehouseNameValue != '') {
                    $warehouseId = $warehouseIdKey;
                    break;
                }
            }
        }
        return $warehouseId;
    }

    public function getAllWarehouseNameEnable()
    {
        $warehouses = array();
        $model = Mage::getModel('inventoryplus/warehouse');
        $collection = $model->getCollection()
                ->addFieldToFilter('status', 1);
        foreach ($collection as $warehouse) {
            $warehouses[$warehouse->getId()] = $warehouse->getWarehouseName();
        }
        return $warehouses;
    }

    public function selectboxWarehouseShipmentByPid($productId, $minQty, $orderItemId, $orderId = null)
    {
        $warehouseOrder = Mage::getModel('inventoryplus/warehouse_order')->getCollection()
                ->addFieldToFilter('order_id', $orderId)
                ->addFieldToFilter('product_id', $productId);

        $allWarehouse = $this->getAllWarehouseNameEnable();
        $warehouseProductModel = Mage::getModel('inventoryplus/warehouse_product')->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->setOrder('total_qty', 'DESC');
        $warehouseHaveProduct = array();
        $return = "<select class='warehouse-shipment' name='warehouse-shipment[items][$orderItemId]' onchange='changeviewwarehouse(this,$orderItemId);' id='warehouse-shipment[items][$orderItemId]'>";
        $firstWarehouse = $warehouseOrder->setPageSize(1)->setCurPage(1)->getFirstItem()->getWarehouseId();
        foreach ($warehouseProductModel as $model) {
            $warehouseId = $model->getWarehouseId();
            $warehouseName = $allWarehouse[$warehouseId];
            $productQty = $model->getTotalQty();
            if ($warehouseName != '') {
                if (!$firstWarehouse)
                    $firstWarehouse = $warehouseId;
                $return .= "<option value='$warehouseId' ";
                if ($warehouseId == $firstWarehouse) {
                    $return .= ' selected';
                }
                $return .= ">$warehouseName($productQty product(s))</option>";
                $warehouseHaveProduct[] = $allWarehouse[$warehouseId];
            }
        }
        foreach ($allWarehouse as $warehouseIdKey => $warehouseNameValue) {
            if ($warehouseNameValue != '') {
                if (in_array($allWarehouse[$warehouseIdKey], $warehouseHaveProduct) == false) {
                    if (!$firstWarehouse)
                        $firstWarehouse = $warehouseIdKey;
                    $return .= "<option value='$warehouseIdKey' ";
                    $return .= ">$warehouseNameValue(0 product(s))</option>";
                }
            }
        }

        $return .= "</select><br />";
        $return .= "<div style='float:right;'><a id='view_warehouse-shipment[items][$orderItemId]' target='_blank' href='" . Mage::getBlockSingleton('inventoryplus/adminhtml_warehouse')->getUrl('adminhtml/inp_warehouse/edit') . 'id/' . $firstWarehouse . "'>" . $this->__('view') . "</a></div>";
        return $return;
    }

    public function getAvailQtyinWarehouseBySku($sku, $warehouseId)
    {
        $productId = Mage::getModel('catalog/product')->getIdBySku($sku);
        $warehouseProduct = Mage::getModel('inventoryplus/warehouse_product')->getCollection()
                ->addFieldToFilter('warehouse_id', $warehouseId)
                ->addFieldToFilter('product_id', $productId)
                ->setPageSize(1)
                ->setCurPage(1)
                ->getFirstItem();
        if ($warehouseProduct->getId()) {
            return $warehouseProduct->getAvailableQty();
        } else {
            return 0;
        }
    }

    /**
     * Get warehouse which is assigned order item to
     * 
     * @param int $storeId
     * @param int $productId
     * @param object $selectWarehouse
     * @param object $ShippingAddress
     * @param object $billingAddress
     * @return int
     */
    public function getQtyProductWarehouse($storeId, $productId, $selectWarehouse, $ShippingAddress, $billingAddress)
    {
        $warehouseId = null;
        switch ($selectWarehouse) {
            case self::LARGEST_QTY_WAREHOUSE :
                $avai = 'MAX';
                $warehouseId = Mage::getResourceModel('inventorywarehouse/inventorywarehouse')->getSelectWarehouse($productId, $avai);
                break;
            case self::SMALLEST_QTY_WAREHOUSE :
                $avai = 'MIN';
                $warehouseId = Mage::getResourceModel('inventorywarehouse/inventorywarehouse')->getSelectWarehouse($productId, $avai);
                break;
            case self::CLOSEST_WAREHOUSE :
                $warehouseId = $this->getClosestWarehouse($productId, $ShippingAddress, $billingAddress);
                break;
            case self::ASSOCIATED_STORE_WAREHOUSE :
                $warehouseId = $this->getWarehouseAssociatedStore($storeId);
                break;
        }
        
        /* if there is no selected warehouse, choose primary warehouse */
        if(!$warehouseId){
            $warehouseId = Mage::helper('inventoryplus/warehouse')->getPrimaryWarehouse()->getId();
        }

        return $warehouseId;
    }
    
    /**
     * 
     * @param int $storeId
     * @return int|null
     */
    public function getWarehouseAssociatedStore($storeId)
    {
        $groupId = Mage::getModel('core/store')->load($storeId)->getGroupId();
        return Mage::getModel('core/store_group')->load($groupId)->getWarehouseId();
    }

    /**
     * 
     * @param int $productId
     * @param object $ShippingAddress
     * @param object $billingAddress
     * @return int|null
     */
    public function getClosestWarehouse($productId, $ShippingAddress, $billingAddress)
    {
        if (!isset($ShippingAddress) || !$ShippingAddress) {
            $ShippingAddress = $billingAddress;
        }

        if (!isset($ShippingAddress) || !$ShippingAddress) {
            Mage::log('Do not find Shipping address and Billing address ----------'
                    . ' method getClosestWarehouse()'
                    . ' class app\code\local\Magestore\Inventorywarehouse\Helper\Warehouse.php ', null, 'inventory_management.log');
            return null;
        }
        $warehouseId = null;
        $distance = -1;
        $warehouses = Mage::getModel('inventoryplus/warehouse')->getCollection();
        foreach ($warehouses as $warehouse) {
            $source_address = $warehouse->getStreet() . " " . $warehouse->getCity() . " " . $warehouse->getCountryId();
            $street = $ShippingAddress->getStreet();
            $destination_address = $street[0] . " " . $ShippingAddress->getCity() . " " . $ShippingAddress->getCountryId();
            $newDistance = $this->calculateDistance($source_address, $destination_address);
            if($newDistance == null) {
                continue;
            }
            if ($distance == -1) {
                $warehouseId = $warehouse->getId();
                $distance = $newDistance;
            } else {
                if ($newDistance && ($distance > $newDistance)) {
                    $warehouseId = $warehouse->getId();
                    $distance = $newDistance;
                }
            }
        }
        return $warehouseId;
    }

    /*
     * get distance between shipping address and warehouse address by google
     */
    public function calculateDistance($source_address, $destination_address)
    {
        $url = "http://maps.googleapis.com/maps/api/directions/json?origin=" . str_replace(' ', '+', $source_address) . "&destination=" . str_replace(' ', '+', $destination_address) . "&sensor=false";
        $ch = Mage::helper('inventoryplus')->curlInit();
        Mage::helper('inventoryplus')->curlSetopt($ch, CURLOPT_URL, $url);
        Mage::helper('inventoryplus')->curlSetopt($ch, CURLOPT_RETURNTRANSFER, 1);
        Mage::helper('inventoryplus')->curlSetopt($ch, CURLOPT_PROXYPORT, 3128);
        Mage::helper('inventoryplus')->curlSetopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        Mage::helper('inventoryplus')->curlSetopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        try{
            $response = Mage::helper('inventoryplus')->curlExec($ch);
            Mage::helper('inventoryplus')->curlClose($ch);
            $response_all = json_decode($response);
            $distance = $response_all->routes[0]->legs[0]->distance->value;
            return $distance;
        } catch(Exception $e) {
            Mage::log($response, null, 'inventory_management.log');
            return null;
        }
    }

    /*
     * get warehouse name by warehouse id in model inventory/warehouse
     */
    public function getWarehouseNameByWarehouseId($warehouseId)
    {
        $warehouseModel = Mage::getModel('inventoryplus/warehouse')->load($warehouseId);
        $warehouseName = $warehouseModel->getWarehouseName();
        return $warehouseName;
    }

    public function checkWarehouseAvailableProduct($warehouseId, $productId, $qty)
    {
        $warehouseProductModel = Mage::getModel('inventoryplus/warehouse_product')->getCollection()
                ->addFieldToFilter('warehouse_id', $warehouseId)
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('total_qty', array('gteq' => $qty));
        if ($warehouseProductModel->setPageSize(1)->setCurPage(1)->getFirstItem()->getData()) {
            return true;
        } else {
            return false;
        }
    }

    public function getWarehouseProductIds($warehouseId)
    {
        $productIds = array();
        $warehouses = Mage::getModel('inventoryplus/warehouse_product')->getCollection()
                ->addFieldToFilter('warehouse_id', $warehouseId);
        foreach ($warehouses as $warehouse) {
            $productIds[] = $warehouse->getProductId();
        }
        return $productIds;
    }

    //get warehouse by product id
    public function getWarehouseByProductId($productId, $checkqtyZero = true)
    {
        if ($checkqtyZero == true) {
            $warehouseProducts = Mage::getModel('inventoryplus/warehouse_product')
                    ->getCollection()
                    ->addFieldToFilter('product_id', $productId)
                    ->addFieldToFilter('qty', array('gt' => 0));
        } else {
            $warehouseProducts = Mage::getModel('inventoryplus/warehouse_product')
                    ->getCollection()
                    ->addFieldToFilter('product_id', $productId);
        }
        if (count($warehouseProducts)) {
            return $warehouseProducts;
        } else {
            return null;
        }
    }

    public function importProduct($data)
    {
        if (count($data)) {
            Mage::getModel('admin/session')->setData('warehouseaddmore_product_import', $data);
            Mage::getModel('admin/session')->setData('null_warehouseaddmore_product_import', 0);
        } else {
            Mage::getModel('admin/session')->setData('null_warehouseaddmore_product_import', 1);
            Mage::getModel('admin/session')->setData('warehouseaddmore_product_import', null);
        }
    }

}
