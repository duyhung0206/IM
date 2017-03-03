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
 * Inventory Adminhtml Controller
 * 
 * @category    Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventoryplus_Adminhtml_Inp_StockController extends Magestore_Inventoryplus_Controller_Action {

    /**
     * Menu Path
     * 
     * @var string 
     */
    protected $_menu_path = 'inventoryplus/stock_control/stock_onhand/managestock';
    protected $_adjustment_created = false;
    protected $_newAdjusment;

    /**
     * init layout and set active for current menu
     *
     * @return Magestore_Inventory_Adminhtml_StockController
     */
    protected function _initAction() {
        $this->loadLayout()
                ->_setActiveMenu($this->_menu_path)
                ->_addBreadcrumb(
                        Mage::helper('adminhtml')->__('Manage Stock'), Mage::helper('adminhtml')->__('Manage Stock')
        );
        return $this;
    }

    /**
     * index action
     */
    public function indexAction() {
        $this->_initAction();
        $this->_title($this->__('Inventory'))
                ->_title($this->__('Manage Stock'));

        $warehouseId = $this->getRequest()->getParam('id');
        if (!$warehouseId) {
            $model = Mage::getModel('inventoryplus/warehouse')->getCollection()
                            ->setPageSize(1)->setCurPage(1)->getFirstItem();
        } else {
            $model = Mage::getModel('inventoryplus/warehouse')->load($warehouseId);
        }

        if ($model->getId() || $warehouseId == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }
            Mage::register('warehouse_data', $model);

            $this->_addBreadcrumb(
                    Mage::helper('adminhtml')->__('Manage Warehouses'), Mage::helper('adminhtml')->__('Manage Warehouses')
            );
            $this->_addBreadcrumb(
                    Mage::helper('adminhtml')->__('Warehouse'), Mage::helper('adminhtml')->__('Warehouse')
            );

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true)
                    ->removeItem('js', 'mage/adminhtml/grid.js')
                    ->addItem('js', 'magestore/adminhtml/inventory/grid.js');
            $this->_addContent($this->getLayout()->createBlock('inventoryplus/adminhtml_stock_edit'))
                    ->_addLeft($this->getLayout()->createBlock('inventoryplus/adminhtml_stock_edit_tabs'));

            Mage::dispatchEvent('stock_controller_index', array('stock_controler' => $this));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('inventoryplus')->__('Stock does not exist!')
            );
            $this->_redirect('*/*/');
        }
    }

    public function productsAction() {
        $warehouseId = Mage::getModel('admin/session')->getData('stock_warehouse_id');

        if (!isset($warehouseId) || !$warehouseId) {
            Mage::getModel('admin/session')->setData('stock_warehouse_id', 0);
        }
        $this->loadLayout();
        $this->getLayout()->getBlock('stock.edit.tab.products')
                ->setProducts($this->getRequest()->getPost('stock_products', null));
        $this->renderLayout();
    }

    public function productsGridAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('stock.edit.tab.products')
                ->setProducts($this->getRequest()->getPost('stock_products', null));
        $this->renderLayout();
    }

    /**
     * save item action
     */
    public function saveAction() {
        $admin = Mage::getSingleton('admin/session')->getUser();
        if (!$data = $this->getRequest()->getPost()) {
            Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('inventoryplus')->__('Unable to find warehouse to save!')
            );
            return $this->_redirect('*/*/');
        }

        if (isset($data['warehouse_id']) && $data['warehouse_id'] != 0) {
            $model = Mage::getModel('inventoryplus/warehouse')->load($data['warehouse_id']);
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('inventoryplus')->__('Unable to find warehouse to save stock!')
            );
            return $this->_redirect('*/*/');
        }

        try {
            $model->setUpdatedBy($admin->getUserName());
            $model->setUpdatedAt(now());
            $model->save();
            $resource = Mage::getSingleton('core/resource');
            $writeConnection = $resource->getConnection('core_write');
            $sqlNews = array();
            $sqlOlds = '';
            $countSqlOlds = 0;
            $productsHistory = array();
            $warehouseProductDeleteds = '';
            $changeProductQtys = array();
            //save products

            if (isset($data['stock_products'])) {
                $warehouseProducts = array();
                $warehouseProductsExplodes = explode('&', urldecode($data['stock_products']));

                if (count($warehouseProductsExplodes) <= 900) {
                    Mage::helper('inventoryplus')->parseStr(urldecode($data['stock_products']), $warehouseProducts);
                } else {
                    foreach ($warehouseProductsExplodes as $warehouseProductsExplode) {
                        $warehouseProduct = '';
                        Mage::helper('inventoryplus')->parseStr($warehouseProductsExplode, $warehouseProduct);
                        $warehouseProducts = $warehouseProducts + $warehouseProduct;
                    }
                }
                if (count($warehouseProducts)) {
                    $deletes = array_keys($warehouseProducts);
                    $warehouseProductDeleteds = Mage::helper('inventoryplus/warehouse')->deleteWarehouseProducts($model, $deletes);
                    $productIds = '';
                    $this->_adjustment_created = false;
                    $manageStock = Mage::getStoreConfig('cataloginventory/item_options/manage_stock', Mage::app()->getStore()->getStoreId());
                    $backorders = Mage::getStoreConfig('cataloginventory/item_options/backorders', Mage::app()->getStore()->getStoreId());
                    $minToChangeStatus = Mage::getStoreConfig('cataloginventory/item_options/min_qty');
                    foreach ($warehouseProducts as $pId => $enCoded) {
                        $codeArr = array();
                        Mage::helper('inventoryplus')->parseStr(Mage::helper('inventoryplus')->base64Decode($enCoded), $codeArr);
                        $warehouseProductsItem = Mage::getModel('inventoryplus/warehouse_product')
                                        ->getCollection()
                                        ->addFieldToFilter('warehouse_id', $model->getId())
                                        ->addFieldToFilter('product_id', $pId)
                                        ->setPageSize(1)->setCurPage(1)->getFirstItem();
                        $storeId = Mage::app()->getStore()->getStoreId();
                        $product = Mage::getModel('catalog/product')->load($pId);
                        if (isset($codeArr['fnsku']) && $codeArr['fnsku'] == $warehouseProductsItem->getFnsku()){}
                        else {
                            if(trim($codeArr['fnsku']) != ''){
                                $product->setFnsku($codeArr['fnsku']);
                                $product->getResource()->saveAttribute($product, 'fnsku');
                            }
                        }
                        if (isset($codeArr['upc']) && $codeArr['upc'] == $warehouseProductsItem->getUpc()){}
                        else {
                            if(trim($codeArr['upc']) != ''){
                                $product->setUpc($codeArr['upc']);
                                $product->getResource()->saveAttribute($product, 'upc');
                            }
                        }

                        if ($warehouseProductsItem->getId()) {
                            $countSqlOlds++;
                            if (isset($codeArr['total_qty']) && $codeArr['total_qty'] == $warehouseProductsItem->getTotalQty()) {
                                if (isset($codeArr['product_location']) && $codeArr['product_location'] == $warehouseProductsItem->getProductLocation()){}
                                else {
                                        $warehouseProductsItem
                                            ->setWarehouseId($model->getId())
                                            ->setProductLocation($codeArr['product_location'])
                                            ->save();
                                    }
                                if (isset($codeArr['fnsku']) && $codeArr['fnsku'] == $warehouseProductsItem->getFnsku()){}
                                else {
                                    if(trim($codeArr['fnsku']) != ''){

                                        $warehouseProductsItem
                                            ->setWarehouseId($model->getId())
                                            ->setFnsku($codeArr['fnsku'])
                                            ->save();
                                    }
                                }
                                if (isset($codeArr['upc']) && $codeArr['upc'] == $warehouseProductsItem->getUpc()){}
                                else {
                                    if(trim($codeArr['upc']) != ''){
                                        $warehouseProductsItem
                                            ->setWarehouseId($model->getId())
                                            ->setUpc($codeArr['upc'])
                                            ->save();
                                    }
                                }
                                continue;
                            }
                            if (isset($codeArr['total_qty']) && !is_numeric($codeArr['total_qty'])) {
//                                if (isset($codeArr['product_location']) && $codeArr['product_location'] == $warehouseProductsItem->getProductLocation())
//                                    continue;
//                                else {
//                                    $warehouseProductsItem
//                                            ->setWarehouseId($model->getId())
//                                            ->setProductLocation($codeArr['product_location'])
//                                            ->save();
//                                    continue;
//                                }
                                if (isset($codeArr['product_location']) && $codeArr['product_location'] == $warehouseProductsItem->getProductLocation()){}
                                else {
                                        $warehouseProductsItem
                                            ->setWarehouseId($model->getId())
                                            ->setProductLocation($codeArr['product_location'])
                                            ->save();
                                }
                                if (isset($codeArr['fnsku']) && $codeArr['fnsku'] == $warehouseProductsItem->getFnsku()){}
                                else {
                                    if(trim($codeArr['fnsku']) != ''){
                                        $warehouseProductsItem
                                            ->setWarehouseId($model->getId())
                                            ->setFnsku($codeArr['fnsku'])
                                            ->save();
                                    }
                                }
                                if (isset($codeArr['upc']) && $codeArr['upc'] == $warehouseProductsItem->getUpc()){}
                                else {
                                    if(trim($codeArr['upc']) != ''){
                                        $warehouseProductsItem
                                            ->setWarehouseId($model->getId())
                                            ->setUpc($codeArr['upc'])
                                            ->save();
                                    }
                                }
                                continue;
                            }
                            $current_warehouse_qty = $warehouseProductsItem->getTotalQty();
                            $changeProductQtys[$pId]['old_qty'] = $current_warehouse_qty;
                            $changeProductQtys[$pId]['new_qty'] = $codeArr['total_qty'];
                            $oldQtyAvailable = $warehouseProductsItem->getAvailableQty();
                            $newQtyAvailable = $oldQtyAvailable + ($codeArr['total_qty'] - $warehouseProductsItem->getTotalQty());
                            $warehouseProductsItem
                                    ->setWarehouseId($model->getId())
                                    ->setTotalQty($codeArr['total_qty'])
                                    ->setAvailableQty($newQtyAvailable)
                                    ->setProductLocation($codeArr['product_location'])
                                    ->setFnsku($codeArr['fnsku'])
                                    ->setUpc($codeArr['upc'])
                                    ->save();
                            $productsHistory[$pId] = array('old' => $current_warehouse_qty, 'new' => $codeArr['total_qty']);
                            $stock_item = Mage::getModel('cataloginventory/stock_item')
                                            ->getCollection()
                                            ->addFieldToFilter('product_id', $pId)
                                            ->setPageSize(1)->setCurPage(1)->getFirstItem();
                            $stock_item_qty = $stock_item->getQty();
                            $new_qty = (int) $stock_item_qty + (int) $codeArr['total_qty'] - $current_warehouse_qty;
                        } else {
                            $current_warehouse_qty = 0;
                            $warehouseProductsNew = Mage::getModel('inventoryplus/warehouse_product');
                            $countSqlOlds++;
                            if ($codeArr['total_qty'] == '')
                                $codeArr['total_qty'] = 0;
                            if (isset($codeArr['total_qty']) && !is_numeric($codeArr['total_qty'])) {
                                if (isset($codeArr['product_location']) && $codeArr['product_location'] == $warehouseProductsItem->getProductLocation()){}
                                else {
                                    $warehouseProductsItem
                                        ->setWarehouseId($model->getId())
                                        ->setProductLocation($codeArr['product_location'])
                                        ->save();
                                }
                                if (isset($codeArr['fnsku']) && $codeArr['fnsku'] == $warehouseProductsItem->getFnsku()){}
                                else {
                                    if(trim($codeArr['fnsku']) != ''){
                                        $warehouseProductsItem
                                            ->setWarehouseId($model->getId())
                                            ->setFnsku($codeArr['fnsku'])
                                            ->save();
                                    }
                                }
                                if (isset($codeArr['upc']) && $codeArr['upc'] == $warehouseProductsItem->getUpc()){}
                                else {
                                    if(trim($codeArr['upc']) != ''){
                                        $warehouseProductsItem
                                            ->setWarehouseId($model->getId())
                                            ->setUpc($codeArr['upc'])
                                            ->save();
                                    }
                                }
                                continue;
                            }
                            $warehouseProductsNew
                                    ->setWarehouseId($model->getId())
                                    ->setProductId($pId)
                                    ->setTotalQty($codeArr['total_qty'])
                                    ->setAvailableQty($codeArr['total_qty'])
                                    ->setProductLocation($codeArr['product_location'])
                                    ->setFnsku($codeArr['fnsku'])
                                    ->setUpc($codeArr['upc'])
                                    ->save();

                            $stock_item = Mage::getModel('cataloginventory/stock_item')
                                            ->getCollection()
                                            ->addFieldToFilter('product_id', $pId)
                                            ->setPageSize(1)->setCurPage(1)->getFirstItem();
                            $stock_item_qty = $stock_item->getQty();
                            $new_qty = (float) $stock_item_qty + (float) $codeArr['total_qty'];
                        }
                        /* update stock status & qty to catalog product */
                        $this->_updateStockItem($stock_item, $manageStock, $backorders, $new_qty, $minToChangeStatus);

                        /* create stock adjustment */
                        $this->_createStockAdjustment($model, $pId, $current_warehouse_qty, $codeArr['total_qty']);

                        $productIds[] = $pId;
                    }
                }
            }

            /* dispatch event */
            Mage::dispatchEvent('inventory_adminhtml_add_new_product', array('data' => $data, 'warehouse' => $model));

            /* save change history */
            $this->_saveChangeHistory($model, $warehouseProductDeleteds, $changeProductQtys);

            Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('inventoryplus')->__('Inventory in the \'%s\' warehouse has been updated successfully.', $model->getWarehouseName())
            );
            Mage::getSingleton('adminhtml/session')->setFormData(false);
            return $this->_redirect('*/*/', array('warehouse_id' => $data['warehouse_id']));
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            Mage::getSingleton('adminhtml/session')->setFormData($data);
            return $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
        }
    }

    protected function _createStockAdjustment($warehouse, $pId, $current_qty, $new_qty) {
        /* create stock adjustment */
        $admin = Mage::getSingleton('admin/session')->getUser();
        if (!$this->_adjustment_created) {
            $adjustment_new = Mage::getModel('inventoryplus/adjuststock')
                    ->setData('warehouse_id', $warehouse->getId())
                    ->setData('reason', Mage::helper('inventoryplus')->__('Perform Quick Update In-grid'))
                    ->setData('warehouse_name', $warehouse->getWarehouseName())
                    ->setData('created_by', $admin->getUsername())
                    ->setData('created_at', now())
                    ->setData('confirmed_by', $admin->getUserName())
                    ->setData('confirmed_at', now())
                    ->setData('status', Magestore_Inventoryplus_Model_Adjuststock::STATUS_COMPLETED)
                    ->save();
            $this->_adjustment_created = true;
            $this->_newAdjusment = $adjustment_new;
        }
        if ($this->_newAdjusment) {
            $adjustproduct_new = Mage::getModel('inventoryplus/adjuststock_product')
                    ->setData('adjuststock_id', $this->_newAdjusment->getId())
                    ->setData('product_id', $pId)
                    ->setData('old_qty', $current_qty)
                    ->setData('adjust_qty', $new_qty)
                    ->save();
        }
    }

    protected function _updateStockItem($stock_item, $manageStock, $backorders, $new_qty, $minToChangeStatus) {
        /* update stock status & qty to catalog product */
        if (!$stock_item->getUseConfigManageStock()) {
            $manageStock = $stock_item->getManageStock();
        }
        if ($manageStock) {
            if (!$stock_item->getUseConfigBackorders()) {
                $backorders = $stock_item->getBackorders();
            }
            $stock_item->setQty($new_qty);
            if ($new_qty <= $minToChangeStatus) {
                $stockStatus = Mage_CatalogInventory_Model_Stock_Status::STATUS_OUT_OF_STOCK;
            } else {
                $stockStatus = Mage_CatalogInventory_Model_Stock_Status::STATUS_IN_STOCK;
            }
            $stockStatus = $backorders ? Mage_CatalogInventory_Model_Stock_Status::STATUS_IN_STOCK : $stockStatus;
            $stock_item->setData('is_in_stock', $stockStatus);
            $stock_item->save();
        }
        /* End of updating stock status & qty to catalog product */
    }

    protected function _saveChangeHistory($warehouse, $warehouseProductDeleteds, $changeProductQtys) {
        /* save change history */
        $admin = Mage::getModel('admin/session')->getUser()->getUsername();
        if ($warehouseProductDeleteds || count($changeProductQtys)) {
            $warehouseHistory = Mage::getModel('inventoryplus/warehouse_history');
            $warehouseHistory->setData('warehouse_id', $warehouse->getId())
                    ->setData('time_stamp', now())
                    ->setData('create_by', $admin)
                    ->save();
            $warehouseHistoryId = $warehouseHistory->getId();

            if (count($changeProductQtys)) {
                foreach ($changeProductQtys as $key => $value) {
                    $productSku = Mage::helper('inventoryplus/warehouse')->getProductSkuByProductId($key);
                    $warehouseHistoryContent = Mage::getModel('inventoryplus/warehouse_historycontent');
                    $warehouseHistoryContent->setData('warehouse_history_id', $warehouseHistoryId)
                            ->setData('field_name', Mage::helper('inventoryplus')->__('%s changed quantity of product(s) with the following SKU(s): %s.', $admin, $productSku))
                            ->setData('old_value', $value['old_qty'])
                            ->setData('new_value', $value['new_qty'])
                            ->save();
                }
            }
            if ($warehouseProductDeleteds) {
                $warehouseHistoryContent = Mage::getModel('inventoryplus/warehouse_historycontent');
                $warehouseHistoryContent->setData('warehouse_history_id', $warehouseHistoryId)
                        ->setData('field_name', Mage::helper('inventoryplus')->__('%s removed product(s) from this warehouse.', $admin))
                        ->setData('new_value', Mage::helper('inventoryplus')->__('%s removed product(s) with the following SKU(s): %s', $admin, $warehouseProductDeleteds))
                        ->save();
            }
        }
        /* End of saving change history */
    }

    /**
     * delete item action
     */
    public function deleteAction() {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $model = Mage::getModel('inventoryplus/inventory');
                $model->setId($this->getRequest()->getParam('id'))
                        ->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('adminhtml')->__('Item was successfully deleted')
                );
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * mass delete item(s) action
     */
    public function massDeleteAction() {
        $inventoryIds = $this->getRequest()->getParam('inventoryplus');
        if (!is_array($inventoryIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($inventoryIds as $inventoryId) {
                    Mage::getModel('inventoryplus/inventory')->setId($inventoryId)->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('adminhtml')->__('Total of %d record(s) were successfully deleted', count($inventoryIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * mass change status for item(s) action
     */
    public function massStatusAction() {
        $inventoryIds = $this->getRequest()->getParam('inventoryplus');
        if (!is_array($inventoryIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
        } else {
            try {
                foreach ($inventoryIds as $inventoryId) {
                    Mage::getSingleton('inventoryplus/inventory')
                            ->load($inventoryId)
                            ->setStatus($this->getRequest()->getParam('status'))
                            ->setIsMassupdate(true)
                            ->save();
                }
                $this->_getSession()->addSuccess(
                        $this->__('Total of %d record(s) were successfully updated', count($inventoryIds))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * export grid item to CSV type
     */
    public function exportCsvAction() {
        $fileName = 'inventory_stock.csv';
        $content = $this->getLayout()
                ->createBlock('inventoryplus/adminhtml_stock_edit_tab_products')
                ->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * export grid item to XML type
     */
    public function exportXmlAction() {
        $fileName = 'inventory_stock.xml';
        $content = $this->getLayout()
                ->createBlock('inventoryplus/adminhtml_stock_edit_tab_products')
                ->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function changewarehouseAction() {
        $warehouseId = $this->getRequest()->getParam('warehouse_id');
        $supplierId = $this->getRequest()->getParam('supplier_id');
        Mage::getModel('admin/session')->setData('stock_warehouse_id', $warehouseId);
        Mage::getModel('admin/session')->setData('stock_supplier_id', $supplierId);
        $storesString = "";
        $result = array();
        $stores = Mage::getModel('core/store_group')
                ->getCollection();
        if ($warehouseId != 0) {
            $stores->addFieldToFilter('warehouse_id', array('eq' => $warehouseId));
        }
        $i = 0;
        foreach ($stores as $store) {
            if ($i != 0) {
                $storesString .= ' | ';
            }
            $storesString .= $store->getName();
            $i++;
        }
        $result['storesString'] = $storesString;
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function showcustomerAction() {
        $block = $this->getLayout()
                ->createBlock('adminhtml/template')
                ->setTemplate('inventoryplus/stock/showcustomer.phtml')
                ->toHtml();
        $this->getResponse()->setBody($block);
    }

}
