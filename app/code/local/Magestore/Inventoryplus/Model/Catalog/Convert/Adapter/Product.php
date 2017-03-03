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
 * Warehouse Edit Block
 * 
 * @category     Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventoryplus_Model_Catalog_Convert_Adapter_Product extends Mage_Catalog_Model_Convert_Adapter_Product {

    protected $_listNewAdjustStock = array();
    protected $_warehouseImports = array();
    protected $_loopStep = 1;
    protected $_newRootAdjust;  /* Storage ID of new adjust stock for root warehouse */

    protected function _checkRowData($importData) {
        if (empty($importData['sku'])) {
            $message = Mage::helper('catalog')->__('Skipping import row, required field "%s" is not defined.', 'sku');
            Mage::throwException($message);
        }
    }

    protected function _getStore($importData) {
        if (empty($importData['store'])) {
            if (!is_null($this->getBatchParams('store'))) {
                $store = $this->getStoreById($this->getBatchParams('store'));
            } else {
                $message = Mage::helper('catalog')->__('Skipping import row, required field "%s" is not defined.', 'store');
                Mage::throwException($message);
            }
        } else {
            $store = $this->getStoreByCode($importData['store']);
        }

        if ($store === false) {
            $message = Mage::helper('catalog')->__('Skipping import row, store "%s" field does not exist.', $importData['store']);
            Mage::throwException($message);
        }

        Mage::register('store_key', $store);

        return $store;
    }

    protected function _prepareProduct($productId, $importData) {
        if ($productId) {
            $product = Mage::getModel('catalog/product')->load($productId);
        } else {
            $productTypes = $this->getProductTypes();
            $productAttributeSets = $this->getProductAttributeSets();

            /**
             * Check product define type
             */
            if (empty($importData['type']) || !isset($productTypes[strtolower($importData['type'])])) {
                $value = isset($importData['type']) ? $importData['type'] : '';
                $message = Mage::helper('catalog')->__('Skip import row, is not valid value "%s" for field "%s"', $value, 'type');
                Mage::throwException($message);
            }
            $product->setTypeId($productTypes[strtolower($importData['type'])]);
            /**
             * Check product define attribute set
             */
            if (empty($importData['attribute_set']) || !isset($productAttributeSets[$importData['attribute_set']])) {
                $value = isset($importData['attribute_set']) ? $importData['attribute_set'] : '';
                $message = Mage::helper('catalog')->__('Skip import row, the value "%s" is invalid for field "%s"', $value, 'attribute_set');
                Mage::throwException($message);
            }
            $product->setAttributeSetId($productAttributeSets[$importData['attribute_set']]);

            foreach ($this->_requiredFields as $field) {
                $attribute = $this->getAttribute($field);
                if (!isset($importData[$field]) && $attribute && $attribute->getIsRequired()) {
                    $message = Mage::helper('catalog')->__('Skipping import row, required field "%s" for new products is not defined.', $field);
                    Mage::throwException($message);
                }
            }
        }

        $this->setProductTypeInstance($product);

        if (isset($importData['category_ids'])) {
            $product->setCategoryIds($importData['category_ids']);
        }

        foreach ($this->_ignoreFields as $field) {
            if (isset($importData[$field])) {
                unset($importData[$field]);
            }
        }

        $store = Mage::registry('store_key');

        if ($store->getId() != 0) {
            $websiteIds = $product->getWebsiteIds();
            if (!is_array($websiteIds)) {
                $websiteIds = array();
            }
            if (!in_array($store->getWebsiteId(), $websiteIds)) {
                $websiteIds[] = $store->getWebsiteId();
            }
            $product->setWebsiteIds($websiteIds);
        }

        if (isset($importData['websites'])) {
            $websiteIds = $product->getWebsiteIds();
            if (!is_array($websiteIds) || !$store->getId()) {
                $websiteIds = array();
            }
            $websiteCodes = explode(',', $importData['websites']);
            foreach ($websiteCodes as $websiteCode) {
                try {
                    $website = Mage::app()->getWebsite(trim($websiteCode));
                    if (!in_array($website->getId(), $websiteIds)) {
                        $websiteIds[] = $website->getId();
                    }
                } catch (Exception $e) {
                    Mage::log($e->getMessage(), null, 'inventory_management.log');
                }
            }
            $product->setWebsiteIds($websiteIds);
            unset($websiteIds);
        }

        return $product;
    }

    protected function _saveProduct($product, $importData) {

        $this->_prepareImportData($product, $importData);

        $this->_prepareStockData($product, $importData);

        $this->_prepareMedia($product, $importData);

        $product->setIsMassupdate(true);
        $product->setExcludeUrlRewrite(true);
        $product->save();

        Mage::register('product_obj', $product);

        return $product;
    }

    protected function _prepareImportData($product, $importData) {
        foreach ($importData as $field => $value) {
            if (in_array($field, $this->_inventoryFields)) {
                continue;
            }
            if (is_null($value)) {
                continue;
            }

            $attribute = $this->getAttribute($field);
            if (!$attribute) {
                continue;
            }

            $isArray = false;
            $setValue = $value;

            if ($attribute->getFrontendInput() == 'multiselect') {
                $value = explode(self::MULTI_DELIMITER, $value);
                $isArray = true;
                $setValue = array();
            }

            if ($value && $attribute->getBackendType() == 'decimal') {
                $setValue = $this->getNumber($value);
            }

            if ($attribute->usesSource()) {
                $options = $attribute->getSource()->getAllOptions(false);

                if ($isArray) {
                    foreach ($options as $item) {
                        if (in_array($item['label'], $value)) {
                            $setValue[] = $item['value'];
                        }
                    }
                } else {
                    $setValue = false;
                    foreach ($options as $item) {
                        if (is_array($item['value'])) {
                            foreach ($item['value'] as $subValue) {
                                if (isset($subValue['value']) && $subValue['value'] == $value) {
                                    $setValue = $value;
                                }
                            }
                        } else if ($item['label'] == $value) {
                            $setValue = $item['value'];
                        }
                    }
                }
            }

            $product->setData($field, $setValue);
        }

        if (!$product->getVisibility()) {
            $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
        }
        return $product;
    }

    protected function _prepareMedia($product, $importData) {
        $mediaGalleryBackendModel = $this->getAttribute('media_gallery')->getBackend();

        $arrayToMassAdd = array();

        foreach ($product->getMediaAttributes() as $mediaAttributeCode => $mediaAttribute) {
            if (isset($importData[$mediaAttributeCode])) {
                $file = trim($importData[$mediaAttributeCode]);
                if (!empty($file) && !$mediaGalleryBackendModel->getImage($product, $file)) {
                    $arrayToMassAdd[] = array('file' => trim($file), 'mediaAttribute' => $mediaAttributeCode);
                }
            }
        }

        $addedFilesCorrespondence = $mediaGalleryBackendModel->addImagesWithDifferentMediaAttributes(
                $product, $arrayToMassAdd, Mage::getBaseDir('media') . DS . 'import', false, false
        );

        foreach ($product->getMediaAttributes() as $mediaAttributeCode => $mediaAttribute) {
            $addedFile = '';
            if (isset($importData[$mediaAttributeCode . '_label'])) {
                $fileLabel = trim($importData[$mediaAttributeCode . '_label']);
                if (isset($importData[$mediaAttributeCode])) {
                    $keyInAddedFile = array_search($importData[$mediaAttributeCode], $addedFilesCorrespondence['alreadyAddedFiles']);
                    if ($keyInAddedFile !== false) {
                        $addedFile = $addedFilesCorrespondence['alreadyAddedFilesNames'][$keyInAddedFile];
                    }
                }

                if (!$addedFile) {
                    $addedFile = $product->getData($mediaAttributeCode);
                }
                if ($fileLabel && $addedFile) {
                    $mediaGalleryBackendModel->updateImage($product, $addedFile, array('label' => $fileLabel));
                }
            }
        }
        return $product;
    }

    protected function _prepareStockData($product, $importData) {
        $stockData = array();
        $inventoryFields = isset($this->_inventoryFieldsProductTypes[$product->getTypeId()]) ? $this->_inventoryFieldsProductTypes[$product->getTypeId()] : array();
        foreach ($inventoryFields as $field) {
            if (isset($importData[$field])) {
                if (in_array($field, $this->_toNumber)) {
                    $stockData[$field] = $this->getNumber($importData[$field]);
                } else {
                    $stockData[$field] = $importData[$field];
                }
            }
        }

        return $product->setStockData($stockData);
    }

    protected function _prepareWarehouseData($importData) {
        $imHelper = Mage::helper('inventoryplus');
        $admin = Mage::getModel('admin/session')->getUser()->getUsername();

        if (!Mage::getModel('admin/session')->getData('inventoryplus_dataflow_warehouse_import_list') && !Mage::getModel('admin/session')->getData('inventoryplus_dataflow_adjust_stock_list')) {
            $warehouseCol = Mage::getModel('inventoryplus/warehouse')->getCollection();
            $listNewAdjustStocks = array();
            $listWarehouseImports = array();
            foreach ($warehouseCol as $warehouse) { /* Create Adjust Stock for each warehouse */
                $f_warehouseId = $warehouse->getId();
                $element = 'warehouse_' . $f_warehouseId;
                if (isset($importData[$element])) { /* If warehouse is in csv file */
                    $listWarehouseImports[] = $f_warehouseId;
                    /* Create adjust stock here */
                    $model = Mage::getModel('inventoryplus/adjuststock');
                    $model->setData('warehouse_id', $f_warehouseId);
                    $model->setData('warehouse_name', $warehouse->getWarehouseName());
                    $model->setData('reason', $imHelper->__('Stock Ajustment available qty in System => Import/Export => Dataflow - Profiles'));
                    $model->setData('created_by', $admin);
                    $model->setData('created_at', now());
                    $model->setData('confirmed_by', $admin);
                    $model->setData('confirmed_at', now());
                    $model->setData('status', 1);
                    $model->save();
                    $listNewAdjustStocks[] = $model->getId();
                    /* Endl Create adjust stock */
                }
            }
            Mage::getModel('admin/session')->setData('inventoryplus_dataflow_warehouse_import_list', $listWarehouseImports);
            Mage::getModel('admin/session')->setData('inventoryplus_dataflow_adjust_stock_list', $listNewAdjustStocks);
        }

        $this->_warehouseImports = Mage::getModel('admin/session')->getData('inventoryplus_dataflow_warehouse_import_list');
        $this->_listNewAdjustStock = Mage::getModel('admin/session')->getData('inventoryplus_dataflow_adjust_stock_list');
    }

    protected function _updateWarehouseStock($importData) {
        $product_id = $this->getProductObj()->getId();
        $oldQty = $importData['inp_old_qty'];
        $imHelper = Mage::helper('inventoryplus');
        $admin = Mage::getModel('admin/session')->getUser()->getUsername();
        $rootWarehouse = Mage::getModel('inventoryplus/warehouse')->getCollection()
                        ->addFieldToFilter('is_root', 1)->setPageSize(1)->setCurPage(1)->getFirstItem();
        if (!$rootWarehouse->getId()) {
            $rootWarehouse = Mage::getModel('inventoryplus/warehouse')->getCollection()
                            ->addFieldToFilter('status', 1)->setPageSize(1)->setCurPage(1)->getFirstItem();
        }
        $newQty = $importData['qty'];

        $this->_prepareWarehouseData($importData);
        
        if (isset($this->_warehouseImports) && $this->_warehouseImports) { /* From step one to end */
            /* Update qty warehouse from csv file */
            $whProductResource = Mage::getResourceModel('inventoryplus/warehouse_product');
            foreach ($this->_warehouseImports as $warehouseId) {
                $qty = $importData['warehouse_' . $warehouseId];
                if ($qty == '' || $qty == null)
                    continue;
                $results = $whProductResource->getItem($warehouseId, $product_id);
                if (count($results) == 0) { /* Create new product => insert into warehouse product table */
                    $whProductResource->insertItem($warehouseId, $product_id, $qty, $qty);
                    $oldAvailQty = $oldTotalQty = 0;
                    $difference = $qty;
                    $newTotalQty = $oldTotalQty + $difference;
                } else { /* Update quantity of product in warehouse  */
                    $oldAvailQty = $results['available_qty'];
                    $difference = $qty - $results['available_qty'];
                    $oldTotalQty = $results['total_qty'];
                    $newTotalQty = $oldTotalQty + $difference;
                    $whProductResource->updateItem($warehouseId, $product_id, $qty, $newTotalQty);
                }
                /* Create adjust stock product here */
                $adjustCol = Mage::getModel('inventoryplus/adjuststock')->getCollection();
                $adjustCol->addFieldToFilter('adjuststock_id', array("IN" => $this->_listNewAdjustStock));
                $adjustCol->addFieldToFilter('warehouse_id', $warehouseId);
                $adjustedModel = $adjustCol->setPageSize(1)->setCurPage(1)->getFirstItem();
                $adjustProduct = Mage::getModel('inventoryplus/adjuststock_product');
                $adjustProduct->setData('adjuststock_id', $adjustedModel->getId());
                $adjustProduct->setData('product_id', $product_id);
                $adjustProduct->setData('old_qty', $oldTotalQty);
                $adjustProduct->setData('suggest_qty', $newTotalQty);
                $adjustProduct->setData('adjust_qty', $newTotalQty);
                $adjustProduct->save();
                /* Endl create adjust stock product */
            }
            /* Re-fix qty catalog by total available qty in warehouse */
            $availQty = $whProductResource->getAvailabelQty($product_id);
            $importData['qty'] = $availQty;
            if ($importData['qty'] != $product->getQty()) {
                $whProductResource->updateCatalogQty($product_id, $importData['qty']);
            }
            /* End Re-fix qty catalog by total available qty in warehouse */
        }

        /* Does not have qty warehouse column. But has qty column */
        $createAdjust = false;
        if ((!isset($this->_warehouseImports) || count($this->_warehouseImports) == 0) && $importData['qty']) {
            $warehouseProduct = Mage::getModel('inventoryplus/warehouse_product')
                    ->getCollection()
                    ->addFieldToFilter('product_id', $product_id)
                    ->setPageSize(1)->setCurPage(1)
                    ->getFirstItem();
            if (!$warehouseProduct->getId()) { /* Add new product */
                Mage::getModel('inventoryplus/warehouse_product')
                        ->setData('warehouse_id', $rootWarehouse->getId())
                        ->setData('product_id', $product_id)
                        ->setData('total_qty', $newQty)
                        ->setData('available_qty', $newQty)
                        ->save();
                $oldAvailQty = $oldTotalQty = 0;
                $newTotalQty = $newAvailQty = $newQty;
                $createAdjust = true;
            } else { /* Update total Qty and Available Qty for is_root warehouse */
                if (floatval($newQty) != floatval($oldQty)) {
                    $oldTotalQty = $warehouseProduct->getTotalQty();
                    $oldAvailQty = $warehouseProduct->getAvailableQty();
                    $difference = $newQty - $oldQty;
                    $newTotalQty = $oldTotalQty + $difference;
                    $newAvailQty = $oldAvailQty + $difference;
                    $warehouseProduct = Mage::getModel('inventoryplus/warehouse_product')
                            ->getCollection()
                            ->addFieldToFilter('warehouse_id', $rootWarehouse->getId())
                            ->addFieldToFilter('product_id', $product_id)
                            ->setPageSize(1)->setCurPage(1)
                            ->getFirstItem();
                    $warehouseProduct->setData('total_qty', $newTotalQty)
                            ->setData('available_qty', $newAvailQty)
                            ->save();
                    $createAdjust = true;
                }
            }
            if ($createAdjust == true) {
                //if($this->_loopStep==1){
                if (!Mage::getModel('admin/session')->getData('inventoryplus_dataflow_adjust_stock_list')) {
                    /* Create adjust stock here */
                    $adjustModel = Mage::getModel('inventoryplus/adjuststock');
                    $adjustModel->setData('warehouse_id', $rootWarehouse->getId());
                    $adjustModel->setData('warehouse_name', $rootWarehouse->getWarehouseName());
                    $adjustModel->setData('reason', $imHelper->__('Stock Ajustment available qty in System => Import/Export  => Dataflow - Profiles'));
                    $adjustModel->setData('created_by', $admin);
                    $adjustModel->setData('created_at', now());
                    $adjustModel->setData('confirmed_by', $admin);
                    $adjustModel->setData('confirmed_at', now());
                    $adjustModel->setData('status', 1);
                    $adjustModel->save();
                    //$this->_newRootAdjust = $adjustModel;
                    Mage::getModel('admin/session')->setData('inventoryplus_dataflow_adjust_stock_list', $adjustModel);
                    /* Endl create adjust stock */
                }
                $this->_newRootAdjust = Mage::getModel('admin/session')->getData('inventoryplus_dataflow_adjust_stock_list');
                /* Create adjust stock product here */
                $_adjustProduct = Mage::getModel('inventoryplus/adjuststock_product');
                $_adjustProduct->setData('adjuststock_id', $this->_newRootAdjust->getId());
                $_adjustProduct->setData('product_id', $product_id);
                $_adjustProduct->setData('old_qty', $oldAvailQty);
                $_adjustProduct->setData('suggest_qty', $newAvailQty);
                $_adjustProduct->setData('adjust_qty', $newAvailQty);
                $_adjustProduct->save();
                /* End create adjust stock product */
            }
        }
    }

    public function saveRow(array $importData) {
        $product = $this->getProductModel()->reset();
        $this->_checkRowData($importData);
        $store = $this->_getStore($importData);
        $product->setStoreId($store->getId());
        $product_id = $product->getIdBySku($importData['sku']);

        $oldQty = Mage::getModel('cataloginventory/stock_item')
                ->loadByProduct($product_id)
                ->getQty();

        $importData['inp_old_qty'] = $oldQty;
        $product = $this->_prepareProduct($product_id, $importData);
        $product = $this->_saveProduct($product, $importData);

        /* Inventoryplus - Import Dataflow integrating */
        if (!(isset($importData['type']) && in_array($importData['type'], array('configurable', 'group', 'bundle')) || in_array($product->getProductTypes(), array('configurable', 'group', 'bundle')))) {
            try {
                $this->_updateWarehouseStock($importData);
                $this->_importSupplierProduct($importData);
                $this->_loopStep++;
            } catch (Exception $e) {
                Mage::log($e->getMessage(), null, 'inventory_management.log');
            }
        }
        /* Endl Inventoryplus - Import Dataflow integrating */
        // Store affected products ids
        $this->_addAffectedEntityIds($product->getId());
        return true;
    }

    protected function _importSupplierProduct($importData) {
        if (isset($importData['suppliers']) && Mage::helper('core')->isModuleEnabled('Magestore_Inventorypurchasing')) {
            /* Insert products into supplier's product */
            $suppliers = explode(",", $importData['suppliers']);
            $suProductResource = Mage::getResourceModel('inventorypurchasing/supplier_product');
            foreach ($suppliers as $supplierId) {
                $results = $suProductResource->getItem($this->getProductObj()->getId(), $supplierId);
                if (count($results) == 0) {
                    $suProductResource->insertItem($this->getProductObj()->getId(), $supplierId);
                }
            }
            /* End Insert products into supplier's product */
        }
    }
    
    public function getProductObj()
    {
        if($product = Mage::registry('product_obj')) {
            return $product;
        } else {
            $product = Mage::getModel('catalog/product');
        }
    }

}
