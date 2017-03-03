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
class Magestore_Inventorypurchasing_Helper_Lowstock extends Mage_Core_Helper_Abstract {

    const LOW_STOCK_QTY_XML_PATH = 'cataloginventory/item_options/notify_stock_qty';

    /**
     * Get low stock colelction separated by Warehouse
     * 
     */
    public function getLowStockWHCollection() {
        $supplierIds = $this->getCurrentSupplierIds();
        $warehouseIds = $this->getCurrentWarehouseIds();
        $productIds = $this->getSelectedProductIds();
        $lowStockQty = Mage::getStoreConfig(self::LOW_STOCK_QTY_XML_PATH);

        $collection = Mage::getResourceModel('inventorypurchasing/product_collection')
                                ->getLowStockCollection($productIds, $warehouseIds, $supplierIds);
        
        return $collection;
    }

    /**
     * 
     * @return type
     */
    public function getAvailableWarehouses() {
        return Mage::getResourceModel('inventoryplus/warehouse_collection')
                        ->addFieldToFilter('status', 1);
    }

    /**
     * 
     * 
     * @return array
     */
    public function getCurrentSupplierIds() {
        $supplierIds = Mage::app()->getRequest()->getPost('supplier_select');
        if (!is_array($supplierIds)) {
            $supplierIds = Mage::getSingleton('adminhtml/session')->getData('currrent_supplier_ids');
        } else {
            Mage::getSingleton('adminhtml/session')->setData('currrent_supplier_ids', $supplierIds);
        }
        $supplierIds = $supplierIds ? $supplierIds : array();
        return $supplierIds;
    }

    /**
     * 
     * 
     * @return array
     */
    public function getCurrentWarehouseIds() {
        $warehouseIds = Mage::app()->getRequest()->getPost('warehouse_select');
        if (!is_array($warehouseIds)) {
            $warehouseIds = Mage::getSingleton('adminhtml/session')->getData('currrent_warehouse_ids');
        } else {
            Mage::getSingleton('adminhtml/session')->setData('currrent_warehouse_ids', $warehouseIds);
        }
        $warehouseIds = $warehouseIds ? $warehouseIds : array();
        return $warehouseIds;
    }
    
    /**
     * 
     * @return array
     */
    public function getSelectedProductIds() {
        $productIds = array();
        $purchasing_products = Mage::app()->getRequest()->getPost('purchasing_products');
        if($purchasing_products) {
            $purchasing_products = explode('&', $purchasing_products);
            foreach($purchasing_products as $purchasing_productId) {
                if($purchasing_productId != 'on')
                    $productIds[] = $purchasing_productId;
            }
        }
        return $productIds;
    }
    
    /**
     * Prepare data to create new draft purchase order
     * 
     * @return array
     */
    public function prepareDataForDraftPO() {
        $data = $this->_prepareGeneralDrafPOData();
        $productData = array();
        $products = $this->getLowStockWHCollection();
        if($products->getSize()) {
            foreach($products as $product) {
                if($product->getPurchaseQty() <= 0) {
                    continue;
                }
                if(isset($productData[$product->getEntityId()]['purchase_more']))
                    $productData[$product->getEntityId()]['purchase_more'] += $product->getPurchaseQty();
                else
                    $productData[$product->getEntityId()]['purchase_more'] = $product->getPurchaseQty();
                
                $productData[$product->getEntityId()]['warehouse_purchase'] = json_encode(array($product->getWarehouseId() => $product->getPurchaseQty()));
            }
        }
        
        if(!count($productData)) {
            return $data;
        }
        $this->chooseLastPurchasedSupplier($productData);
        $data['product_data'] = $productData;
        return $data;
    }  
    
     /**
     * Prepare general data of new draft purchase order
     * 
     * @return array
     */
    protected function _prepareGeneralDrafPOData(){
        $data = array();
        $request = Mage::app()->getRequest();
        $data['currency'] = $request->getParam('currency') ? $request->getParam('currency') : $request->getPost('currency');
        $data['currency'] = $data['currency'] ? $data['currency'] : Mage::app()->getStore()->getBaseCurrency()->getCode();;
        $data['change_rate'] = $request->getParam('change_rate') ? $request->getParam('change_rate') : $request->getPost('change_rate');
        $data['change_rate'] = $data['change_rate'] ? $data['change_rate'] : 1;
        $data['warehouses'] = json_encode($this->getCurrentWarehouseIds());
        $data['suppliers'] = json_encode($this->getCurrentSupplierIds());
        $data['purchase_rate'] = 1;
        return $data;
    }   
    
    /**
     * Choose last purchased supplier for products
     * 
     * @param array $productData
     * @return array
     */
    public function chooseLastPurchasedSupplier(&$productData){
        if (!count($productData)) {
            return $productData;
        }
        //get last purchased suppliers
        $poProducts = Mage::getResourceModel('inventorypurchasing/purchaseorder_product_collection')
                            ->getLastSupplierProduct(array_keys($productData));
                            
        if($poProducts->getSize()){
            foreach($poProducts as $poProduct){
                $suplierIds = explode(',', $poProduct->getData('list_supplier'));
                if(count($suplierIds)){
                    $productData[$poProduct->getProductId()]['supplier_id'] = reset($suplierIds);
                }
            }
        }
        //check if need to choose default supplier
        $needChooseDefault = false;
        foreach($productData as $productItem){
            if(!isset($productItem['supplier_id']) || !$productItem['supplier_id']){
                $needChooseDefault = true;
                break;
            }
        }
        if($needChooseDefault){
            $this->chooseDefaultSupplier($productData);
        }
        return $productData;
    }  
    
    /**
     * Choose default suppliers for products
     * 
     * @param array $productData
     * @return array
     */
    public function chooseDefaultSupplier(&$productData) {
        if (!count($productData)) {
            return $productData;
        }
        $supplierProducts = Mage::getResourceModel('inventorypurchasing/supplier_product_collection')
                ->addFieldToFilter('product_id', array('in' => array_keys($productData)));
        if (count($supplierProducts)) {
            $supplierList = array();
            //prepare supplier list
            foreach ($supplierProducts as $supplierProduct) {
                $supplyData = $supplierProduct->getData();
                $supplyData['final_cost'] = $supplierProduct->getCost() * (100 + $supplierProduct->getTax() - $supplierProduct->getDiscount()) / 100;
                $supplierList[$supplierProduct->getProductId()][] = $supplyData;
            }
            //choose supplier for each products
            foreach ($productData as $productId => $data) {
                if(isset($productData[$productId]['supplier_id']) && $productData[$productId]['supplier_id'] )
                    continue;
                $suppliers = isset($supplierList[$productId]) ? $supplierList[$productId] : array();
                if (count($suppliers)) {
                    //sort by final_cost asc
                    usort($suppliers, array($this, 'compareSupplierFinalCost'));
                    //get first supplier
                    $productData[$productId]['supplier_id'] = $suppliers[0]['supplier_id'];
                }
            }
        }
        return $productData;
    }
    
    /**
     * Compare two suppliers
     * 
     * @param array $supplierA
     * @param array $supplierB
     * @return int
     */
    public function compareSupplierFinalCost($supplierA, $supplierB) {
        return $this->compareSupplier('final_cost', $supplierA, $supplierB);
    }

    /**
     * Compare two suppliers
     * 
     * @param string $field
     * @param array $supplierA
     * @param array $supplierB
     * @return int
     */
    public function compareSupplier($field, $supplierA, $supplierB) {
        if ($supplierA[$field] == $supplierB[$field])
            return 0;
        if ($supplierA[$field] < $supplierB[$field])
            return -1;
        return 1;
    }
    
    public function prepareParams() {
        return array('warehouse_select' => $this->getCurrentWarehouseIds() ,
                    'supplier_select' => $this->getCurrentSupplierIds());
    }    

}
