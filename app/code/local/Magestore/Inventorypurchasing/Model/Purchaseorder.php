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
 * @category 	Magestore
 * @package 	Magestore_Inventorysupplier
 * @copyright 	Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license 	http://www.magestore.com/license-agreement.html
 */

/**
 * Inventorysupplier Model
 * 
 * @category 	Magestore
 * @package 	Magestore_Inventorysupplier
 * @author  	Magestore Developer
 */
class Magestore_Inventorypurchasing_Model_Purchaseorder extends Mage_Core_Model_Abstract {

    /**
     * Purchase order status
     */
    const AWAITING_DELIVERY_STATUS = 5;
    const COMPLETE_STATUS = 6;
    const CANCELED_STATUS = 7;
    const PENDING_STATUS = 8;
    const WAITING_APPROVE_STATUS = 9; //waiting approval from Warehouse Manager
    const WAITING_CONFIRM_STATUS = 10; //waiting confirmation from Supplier
    const RECEIVING_STATUS = 11; //after created the first delivery
    
    const SHIPPING_TAX_CONFIG_PATH = 'inventoryplus/purchasing/shipping_includes_tax';
    const DEFAULT_SHIPPING_COST_CONFIG_PATH = 'inventoryplus/purchasing/default_shipping_cost';
    const DISCOUNT_TAX_CONFIG_PATH = 'inventoryplus/purchasing/apply_after_discount';
    const DEFAULT_TAX_CONFIG_PATH = 'inventoryplus/purchasing/default_tax';
    
    /*
     * Deleted Purchase order status
     */
    const IS_TRASH= 1;
    const IS_NOT_TRASH= 0;

    const PAID = 1;
    const PARTIALLY_PAID = 2;

    protected $_eventPrefix = 'inventorypurchasing_purchaseorder';
    protected $_eventObject = 'inventorypurchasing_purchaseorder';
    
    
    public function _construct() {
        parent::_construct();
        $this->_init('inventorypurchasing/purchaseorder');
    }

    public function getPurhcaseOrderHistory($id) {
        return Mage::getModel('inventorypurchasing/purchaseorder_history')->load($id);
    }
    
    public function getPurchaseOrderContentByHistoryId($id) {
        $collection = Mage::getModel('inventorypurchasing/purchaseorder_historycontent')
                ->getCollection()
                ->addFieldToFilter('purchase_order_history_id', $id);
        return $collection;
    }
    
    /**
     * Create new purchase order
     * 
     * @param array $data
     * @return \Magestore_Inventorypurchasing_Model_Purchaseorder
     */
    public function create($data){
        //var_dump($data);die();
        $this->setData($data)->save();
        if(count($data['products'])){
            foreach($data['products'] as $productData){
                $poProduct = Mage::getModel('inventorypurchasing/purchaseorder_product');
                $poProduct->setData($productData)
                            ->setPurchaseOrderId($this->getId())
                            ->save();
                $poProduct->setId(null);
                if(count($productData['warehouse_qty'])){
                    foreach($productData['warehouse_qty'] as $warehouseId=>$qty){
                        $poProductWarehouse = Mage::getModel('inventorypurchasing/purchaseorder_productwarehouse');
                        $poProductWarehouse->setData($productData)
                                            ->setPurchaseOrderId($this->getId())
                                            ->setWarehouseId($warehouseId)
                                            ->setWarehouseName($productData['warehouse_names'][$warehouseId])
                                            ->setQtyOrder($qty)
                                            ->save();
                        $poProductWarehouse->setId(null);
                    }
                }
            }
        }
        return $this;
    }

    /**
     * all status of purchase order cannot delete supplier
     * @return array
     */
    public function getStatusCannotDeleteSupplier()
    {
        return array(
            self::AWAITING_DELIVERY_STATUS,
            self::PENDING_STATUS,
            self::WAITING_APPROVE_STATUS,
            self::WAITING_CONFIRM_STATUS,
            self::RECEIVING_STATUS
        );
    }

}
