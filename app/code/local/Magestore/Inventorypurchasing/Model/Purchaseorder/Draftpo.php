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

class Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo extends Mage_Core_Model_Abstract {
    
    CONST SUPPLYNEED_TYPE = 1;
    CONST LOWSTOCK_TYPE = 2;
    CONST PENDINGORDER_TYPE = 3;
    
    public function _construct() {
        parent::_construct();
        $this->_init('inventorypurchasing/purchaseorder_draftpo');
    }

    /**
     * Create a new draft purchase order
     * 
     * @return \Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo
     */
    public function create() {
        $this->save();
        $this->_addProducts();
        return $this;
    }

    /**
     * Update data
     * 
     * @param array $data [attribute, product_id, warehouse_id]
     * @return \Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo
     */
    public function update($data) {
        if (isset($data['product_id'])) {
            $product = $this->getProductCollection()
                    ->addFieldToFilter('product_id', $data['product_id'])
                    ->setPageSize(1)->setCurPage(1)
                    ->getFirstItem();
            if ($data['attribute'] != 'warehouse_purchase') {
                $product->setData($data['attribute'], $data['value']);
            } else {
                $warehousePurchases = json_decode($product->getWarehousePurchase(), true);
                $warehousePurchases[$data['warehouse_id']] = (float) $data['value'];
                $product->setPurchaseMore(array_sum($warehousePurchases));
                $product->setData('warehouse_purchase', json_encode($warehousePurchases));
            }
            return $product->save();
        }
        return $this;
    }

    /**
     * Rewrite _afterSave
     * 
     * @return \Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo
     */
    protected function _afterSave() {
        parent::_afterSave();
        if ($this->getIsUpdateMode()) {
            $this->_removeUncheckProducts();
            $this->_addProducts();
        }
        return $this;
    }

    /**
     * Remove unchecked products
     * 
     * @return \Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo
     */
    protected function _removeUncheckProducts() {
        $productIds = count($this->getProductData()) ? array_keys($this->getProductData()) : array();
        $products = $this->getProductCollection()
                ->addFieldToFilter('product_id', array('nin' => $productIds));
        if (count($products)) {
            foreach ($products as $product) {
                $product->delete();
            }
        }
        return $this;
    }

    /**
     * Add/ update products to draft purchase order
     * 
     * @return \Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo
     */
    protected function _addProducts() {
        if (!count($this->getProductData())) {
            return $this;
        }
        foreach ($this->getProductData() as $productId => $productData) {
            $this->prepareProductData($productData);
            $draftPOProduct = Mage::getModel('inventorypurchasing/purchaseorder_draftpo_product');
            $draftPOProduct->setDraftPoId($this->getId())
                    ->addData($productData)
                    ->setProductId($productId)
                    ->save();
            $draftPOProduct->setId(null);
        }
        return $this;
    }

    /**
     * Add product to draft purchase order
     * 
     * @param string|int $productId
     * @return \Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo
     */
    public function addProduct($productId) {
        //check if product has been added
        $existedProduct = $this->getProductCollection()
                ->addFieldToFilter('product_id', $productId)
                ->setPageSize(1)->setCurPage(1)
                ->getFirstItem();
        if ($existedProduct->getId()) {
            throw new Exception($this->_helper()->__('Product has been existed in this draft purchase order.'));
        }
        //get supplier id
        $poSupplier = Mage::getResourceModel('inventorypurchasing/supplier_product_collection')
                ->addFieldToFilter('product_id', $productId)
                ->setPageSize(1)->setCurPage(1)
                ->getFirstItem();
        if (!$poSupplier->getId()) {
            throw new Exception($this->_helper()->__('There is no supplier for this product.'));
        }
        //prepare data to save
        $productData = array($productId => array('purchase_more' => 0));
        $this->_helper()->chooseLastPurchasedSupplier($productData);
        $this->_helper()->calculateWarehouseQty($productData);
        $this->setProductData($productData)->_addProducts();
        return $this;
    }

    /**
     * Prepare data for saving product to draft purchase order
     * 
     * @param array $productData
     * @return \Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo
     */
    public function prepareProductData(&$productData) {
        $warehousePurchases = array();
        if (!isset($productData['warehouse_purchase']) || !$productData['warehouse_purchase']) {
            if (count($productData)) {
                foreach ($productData as $field => $data) {
                    if (strpos($field, 'warehouse_')!== false) {
                        $warehouseId = str_replace('warehouse_', '', $field);
                        $warehousePurchases[$warehouseId] = $data;
                    }
                }
            }
            $productData['warehouse_purchase'] = json_encode($warehousePurchases);
        }
        return $this;
    }

    /**
     * Get product collection
     * 
     * @return \Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo_Product_Collection
     */
    public function getProductCollection() {
        $collection = Mage::getResourceModel('inventorypurchasing/purchaseorder_draftpo_product_collection')
                ->addFieldToFilter('draft_po_id', $this->getId());
        return $collection;
    }

    /**
     * Create purchase orders from draft
     * 
     */
    public function createPurchaseOrders() {
        $products = $this->getProductCollection();
        $purchaseOrders = array();
        foreach ($products as $product) {
            if (!$product->getPurchaseMore() || !$product->getSupplierId())
                continue;
            $purchaseOrders[$product->getSupplierId()][$product->getProductId()] = $product->loadData();
        }

        foreach ($purchaseOrders as $supplierId => $productData) {
            if (empty($productData))
                continue;
            $purchaseOrderData = $this->preparePOData(array('supplier_id' => $supplierId, 'products' => $productData));
            $this->createPO($purchaseOrderData);
        }
    }

    /**
     * Create a purchase order
     * 
     * @param array $data
     */
    public function createPO($data) {
        $purchaseOrder = Mage::getModel('inventorypurchasing/purchaseorder')
                ->create($data);
        return $purchaseOrder;
    }

    /**
     * Prepare data to create purchase order
     * 
     * @param array $data
     * @return array
     */
    public function preparePOData($data) {
        $supplier = Mage::getModel('inventorypurchasing/supplier')->load($data['supplier_id']);
        //general data
        $data['purchase_on'] = Mage::getModel('core/date')->date();
        $data['payment_date'] = Mage::getModel('core/date')->date();
        $data['expected_date'] = Mage::getModel('core/date')->date();
        $data['canceled_date'] = date('Y-m-d H:i:s', strtotime(Mage::getModel('core/date')->date()) + 24 * 3600);
        $data['started_date'] = Mage::getModel('core/date')->date();
        $data['supplier_name'] = $supplier->getSupplierName();
        $data['bill_name'] = $supplier->getContactName();
        $data['currency'] = $this->getCurrency();
        $data['change_rate'] = $this->getChangeRate();
        $data['created_by'] = $this->getCreatedBy();
        $data['status'] = Magestore_Inventorypurchasing_Model_Purchaseorder::PENDING_STATUS;
        $data['shipping_tax'] = Mage::getStoreConfig(Magestore_Inventorypurchasing_Model_Purchaseorder::SHIPPING_TAX_CONFIG_PATH);
        $data['discount_tax'] = Mage::getStoreConfig(Magestore_Inventorypurchasing_Model_Purchaseorder::DISCOUNT_TAX_CONFIG_PATH);        
        $data['tax_rate'] = floatval(Mage::getStoreConfig(Magestore_Inventorypurchasing_Model_Purchaseorder::DEFAULT_TAX_CONFIG_PATH));
        $data['shipping_cost'] = floatval(Mage::getStoreConfig(Magestore_Inventorypurchasing_Model_Purchaseorder::DEFAULT_SHIPPING_COST_CONFIG_PATH));
        $totalProduct = 0;
        //warehouse data
        $warehouseIds = array();
        foreach ($data['products'] as $product) {
            $totalProduct += $product['purchase_more'];
            $warehouses = $product['warehouse_purchase'];
            $warehouseIds = array_unique(array_merge($warehouseIds, array_keys($warehouses)));
        }
        $data['total_products'] = $totalProduct;
        $data['warehouse_id'] = implode(',', $warehouseIds);
        $warehouseNames = array();
        $warehouses = Mage::getModel('inventoryplus/warehouse')
                ->getCollection()
                ->addFieldToFilter('warehouse_id', array('in' => $warehouseIds));
        if (count($warehouses)) {
            foreach ($warehouses as $warehouse) {
                $warehouseNames[$warehouse->getId()] = $warehouse->getWarehouseName();
            }
        }
        $data['warehouse_name'] = implode(', ', $warehouseNames);
        //product data
        $collection = Mage::getResourceModel('inventorypurchasing/supplier_product_collection')
                ->addFieldToFilter('product_id', array('in' => array_keys($data['products'])));
        $collection->getSelect()
                ->joinLeft(
                        array('product' => $collection->getTable('catalog/product')), 'main_table.product_id = product.entity_id', array('sku'));

        $tableAlias = 'name_table';
        $attribute = Mage::getSingleton('eav/config')
                ->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'name');
        $collection->getSelect()->joinLeft(
                array($tableAlias => $attribute->getBackendTable()), "main_table.product_id = $tableAlias.entity_id AND $tableAlias.attribute_id={$attribute->getId()}", array('product_name' => 'value')
        );
        $collection->getSelect()->group("main_table.product_id");
        $totalAmount = 0;
        foreach ($collection as $item) {
            if(!isset($data['products'][$item->getProductId()]['purchase_more']))
                continue;
            if (!$qty = $data['products'][$item->getProductId()]['purchase_more'])
                continue;
            $productData = array(
                'cost' => $item->getCost(),
                'discount' => $item->getDiscount(),
                'tax' => $item->getTax(),
                'product_sku' => $item->getSku(),
                'product_name' => $item->getProductName(),
                'qty' => $qty,
                'supplier_sku' => $item->getSupplierSku(),
                'product_id' => $item->getProductId(),
                'warehouse_qty' => $data['products'][$item->getProductId()]['warehouse_purchase'],
                'warehouse_names' => $warehouseNames,
            );
            $data['products'][$item->getProductId()] = $productData;
            $taxItem = $item->getTax() ? $item->getTax() : $data['tax_rate'];
            if ($data['discount_tax'] == 0) {
                /* Tax before discount */
                $totalAmount += $qty * $item->getCost() * (100 + $taxItem - $item->getDiscount()) / 100;
            } else {
                /* Tax after discount */
                $totalAmount += $qty * $item->getCost() * (1 + $taxItem/100) * (1 - $item->getDiscount()/100);
            }
        }
        $data['total_amount'] = $totalAmount;
        return $data;
    }

    /**
     * Remove purchase orders if there is error
     * 
     */
    public function rollbackPO() {
        
    }

    /**
     * Get helper object
     * 
     * @return \Magestore_Inventorypurchasing_Helper_Draftpo
     */
    protected function _helper() {
        return Mage::helper('inventorypurchasing/draftpo');
    }

    /**
     * Get Sales history date range
     * 
     * @return array
     */
    public function getSalesDateRange() {
        $range = array();
        $range['from'] = Mage::getModel('core/date')->date('Y-m-d 00:00:00', strtotime($this->getSalesFrom()));
        $range['to'] = Mage::getModel('core/date')->date('Y-m-d 23:59:59', strtotime($this->getSalesTo()));
        $range['count'] = $return['count'] = floor((strtotime($range['to']) - strtotime($range['from'])) / (60 * 60 * 24));;
        return $range;
    }
    
    /**
     * Get Forecast supply need days
     * 
     * @return int
     */    
    public function getNumberDaysForecast() {
        $range = array();
        $range['from'] = Mage::getModel('core/date')->date('Y-m-d 00:00:00');
        $range['to'] = Mage::getModel('core/date')->date('Y-m-d 23:59:59', $this->getForecastTo());
        $totalDays = floor((strtotime($range['to']) - strtotime($range['from'])) / (60 * 60 * 24));
        return $totalDays;        
    }
    
    /**
     * 
     * @return string
     */
    public function getSalesDateRangeType(){
        return $this->getData('daterange_type');
    }

}
