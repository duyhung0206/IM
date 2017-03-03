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
 * Inventorypurchasing Helper
 * 
 * @category    Magestore
 * @package     Magestore_Inventorypurchasing
 * @author      Magestore Developer
 */
class Magestore_Inventorypurchasing_Helper_Draftpo extends Mage_Core_Helper_Abstract {

    const SUPPLIER_TYPE_LOWEST_COST = 1;
    const SUPPLIER_TYPE_LAST_PURCHASE = 2;

    /**
     * 
     * @param array $productData
     * @return array
     */
    public function calculateWarehouseQty(&$productData) {
        if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorysupplyneeds')) {
            $helper = Mage::helper('inventorysupplyneeds');
            return $helper->calculateWarehouseQty($productData);
        }
        return $productData;
    }    
    /**
     * 
     * @return array
     */
    public function getWarehouseSelected() {
        if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorysupplyneeds')) {
            $helper = Mage::helper('inventorysupplyneeds');
            $filter = Mage::app()->getRequest()->getParam('top_filter');
            $helper->setTopFilter($filter);
            return $helper->getWarehouseSelected();
        }
        if ($this->_getDraftPO()) {
            return json_decode($this->_getDraftPO()->getWarehouses(), true);
        }
        $warehousesEnable = Mage::helper('inventoryplus/warehouse')->getAllWarehouseNameEnable();
        return array_keys($warehousesEnable);
    }

    /**
     * 
     * @param int $primaryId
     * @param array $warehouseQtys
     * @return int
     */
    protected function _selectDefaultWarehouse($primaryId, $warehouseIds) {
        $defaultWarehouseId = null;
        if (isset($warehouseQty[$primaryId])) {
            $defaultWarehouseId = $primaryId;
        } else {
            foreach ($warehouseIds as $warehouseId) {
                $defaultWarehouseId = $warehouseId;
                break;
            }
        }
        return $defaultWarehouseId;
    }

    /**
     * Check edit mode
     * 
     * @return boolean
     */
    public function isEditModeDraftPO() {
        if (Mage::app()->getRequest()->getParam('id')) {
            return true;
        }
        return false;
    }

    /**
     * Prepare general data of new draft purchase order
     * 
     * @return array
     */
    protected function _prepareGeneralDrafPOData() {
        $data = array();
        $request = Mage::app()->getRequest();
        $data['currency'] = $request->getParam('currency') ? $request->getParam('currency') : $request->getPost('currency');
        $data['change_rate'] = $request->getParam('change_rate') ? $request->getParam('change_rate') : $request->getPost('change_rate');
        $dateRange = $this->getSalesFromTo();
        $data['sales_from'] = $dateRange['from'];
        $data['sales_to'] = $dateRange['to'];
        $data['daterange_type'] = $this->getHistorySelected();
        $data['forecast_to'] = $this->getForecastTo();
        $data['warehouses'] = json_encode($this->getWarehouseSelected());
        $data['suppliers'] = json_encode($this->getsupplierSelected());
        $data['purchase_rate'] = $this->getRatePurchaseMore() / 100;
        return $data;
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
                if (isset($productData[$productId]['supplier_id']) && $productData[$productId]['supplier_id'])
                    continue;
                $suppliers = isset($supplierList[$productId]) ? $supplierList[$productId] : array();
                if (!empty($suppliers)) {
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
     * Choose last purchased supplier for products
     * 
     * @param array $productData
     * @return array
     */
    public function chooseLastPurchasedSupplier(&$productData) {
        if (!count($productData)) {
            return $productData;
        }

        $poProductResource = Mage::getResourceModel('inventorypurchasing/purchaseorder_product');
        //get last purchased suppliers
        $poProducts = $poProductResource->getLastPurchasedSuppliers($productData);
        if (count($poProducts)) {
            foreach ($poProducts as $poProduct) {
                $suplierIds = explode(',', $poProduct->getData('list_supplier'));
                if (!empty($suplierIds)) {
                    $productData[$poProduct->getProductId()]['supplier_id'] = reset($suplierIds);
                }
            }
        }
        //check if need to choose default supplier
        $needChooseDefault = false;
        foreach ($productData as $productItem) {
            if (!isset($productItem['supplier_id']) || !$productItem['supplier_id']) {
                $needChooseDefault = true;
                break;
            }
        }
        if ($needChooseDefault) {
            $this->chooseDefaultSupplier($productData);
        }
        return $productData;
    }

    /**
     * Mass change suppliers of draft purchase order
     * 
     * @param string|int $id
     * @param string|int $type
     * @return \Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo
     */
    public function massChangeSupplier($id, $type) {
        $draftPO = Mage::getModel('inventorypurchasing/purchaseorder_draftpo')->load($id);
        $products = $draftPO->getProductCollection();
        $productData = array();
        foreach ($products as $product) {
            $productData[$product->getProductId()] = $product->getData();
        }
        switch ($type) {
            case self::SUPPLIER_TYPE_LOWEST_COST:
                $this->chooseDefaultSupplier($productData);
                break;
            case self::SUPPLIER_TYPE_LAST_PURCHASE:
                $this->chooseLastPurchasedSupplier($productData);
                break;
        }
        $draftPO->setProductData($productData)
                ->setIsUpdateMode(true)
                ->save();
        return $draftPO;
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

    /**
     * Prepare data to save to draft purchase order
     * 
     * @param string $field
     * @param string $value
     * @return array
     */
    public function prepareUpdateData($field, $value) {
        $explodedField = explode(';', str_replace('[', ';', str_replace(']', ';', $field)));
        $attribute = $explodedField[0];
        $productId = $explodedField[1];
        $warehouseId = isset($explodedField[3]) ? $explodedField[3] : null;
        $updateData = array(
            'attribute' => $attribute,
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'value' => $value,
        );
        return $updateData;
    }

    /**
     * 
     * @return \Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo
     */
    public function getDraftPO() {
        return Mage::getResourceModel('inventorypurchasing/purchaseorder_draftpo_collection')
                        ->setOrder('draft_po_id', 'DESC')
                        ->setPageSize(1)->setCurPage(1)->getFirstItem();
    }

    /**
     * 
     * @return \Magestore_Inventorypurchasing_Model_Purchaseorder_Draftpo
     */
    protected function _getDraftPO() {
        return Mage::registry('draftpo');
    }

}
