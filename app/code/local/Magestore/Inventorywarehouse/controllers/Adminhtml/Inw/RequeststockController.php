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
 * Inventorywarehouse Adminhtml Controller
 *
 * @category    Magestore
 * @package     Magestore_Inventorywarehouse
 * @author      Magestore Developer
 */
class Magestore_Inventorywarehouse_Adminhtml_Inw_RequeststockController
    extends Magestore_Inventoryplus_Controller_Action
    implements Magestore_Inventoryplus_Controller_Scan
{

    /**
     * Menu Path
     *
     * @var string
     */
    protected $_menu_path = 'inventoryplus/stock_control/transfer_stock/requeststock';


    public function indexAction()
    {
        $this->_title($this->__('Inventory'))
            ->_title($this->__('Request Stock'));
        $this->loadLayout()->_setActiveMenu($this->_menu_path);
        $this->renderLayout();
    }

    public function newAction()
    {
        $this->_title($this->__('Inventory'))
            ->_title($this->__('Request Stock'));
        $this->loadLayout()->_setActiveMenu('inventoryplus');
        $this->_setActiveMenu($this->_menu_path);
        $this->_addBreadcrumb(
            Mage::helper('adminhtml')->__('Stock Requesting Manager'), Mage::helper('inventorywarehouse')->__('Stock Transfering Manager')
        );
        $this->_addBreadcrumb(
            Mage::helper('adminhtml')->__('Stock Requesting News'), Mage::helper('inventorywarehouse')->__('Stock Transfering News')
        );
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true)
            ->removeItem('js', 'mage/adminhtml/grid.js')
            ->addItem('js', 'magestore/adminhtml/inventory/grid.js');
        $this->_addContent($this->getLayout()->createBlock('inventorywarehouse/adminhtml_requeststock_edit'))
            ->_addLeft($this->getLayout()->createBlock('inventorywarehouse/adminhtml_requeststock_edit_tabs'));
        $this->renderLayout();
    }

    public function editAction()
    {
        $this->_title($this->__('Inventory'))
            ->_title($this->__('Request Stock'));
        $requeststock = $this->getRequest()->getParam('id');
        $model = Mage::getModel('inventorywarehouse/requeststock')->load($requeststock);
        if ($model->getId() || $requeststock == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);

            if (!empty($data)) {
                $model->setData($data);
            }
            Mage::register('requeststock_data', $model);

            $this->loadLayout()->_setActiveMenu('inventoryplus');
            $this->_setActiveMenu($this->_menu_path);

            $this->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Manage Stock Requests'), Mage::helper('adminhtml')->__('Manage Stock Requests')
            );
            $this->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Request Stock News'), Mage::helper('adminhtml')->__('Request Stock News')
            );

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true)
                ->removeItem('js', 'mage/adminhtml/grid.js')
                ->addItem('js', 'magestore/adminhtml/inventory/grid.js');
            $this->_addContent($this->getLayout()->createBlock('inventorywarehouse/adminhtml_requeststock_edit'))
                ->_addLeft($this->getLayout()->createBlock('inventorywarehouse/adminhtml_requeststock_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('inventorywarehouse')->__('Requesting stock does not exist')
            );
            $this->_redirect('*/*/');
        }
    }

    public function productsAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('requeststock.edit.tab.products')
            ->setProducts($this->getRequest()->getPost('requeststock_products', null));
        $this->renderLayout();
        if (Mage::getModel('admin/session')->getData('requeststock_product_import'))
            Mage::getModel('admin/session')->setData('requeststock_product_import', null);
    }

    public function productsdeliveryAction()
    {
        $this->loadLayout();
        $this->renderLayout();
//        $this->renderLayout();
//        if (Mage::getModel('admin/session')->getData('requeststock_product_import'))
//            Mage::getModel('admin/session')->setData('requeststock_product_import', null);
    }

    public function productsGridAction()
    {
        $this->loadLayout();
        $this->getLayout()->getBlock('requeststock.edit.tab.products')
            ->setProducts($this->getRequest()->getPost('requeststock_products', null));
        $this->renderLayout();
        if (Mage::getModel('admin/session')->getData('requeststock_product_import'))
            Mage::getModel('admin/session')->setData('requeststock_product_import', null);
    }

    public function checkproductAction()
    {
        $requeststock_products = $this->getRequest()->getPost('products');
        $checkProduct = 0;
        $next = false;
        if (isset($requeststock_products)) {
            $stockrequestProducts = array();
            $stockrequestProductsExplodes = explode('&', urldecode($requeststock_products));
            if (count($stockrequestProductsExplodes) <= 900) {
                Mage::helper('inventoryplus')->parseStr(urldecode($requeststock_products), $stockrequestProducts);
            } else {
                foreach ($stockrequestProductsExplodes as $stockrequestProductsExplode) {
                    $stockrequestProduct = '';
                    Mage::helper('inventoryplus')->parseStr($stockrequestProductsExplode, $stockrequestProduct);
                    $stockrequestProducts = $stockrequestProducts + $stockrequestProduct;
                }
            }
            if (count($stockrequestProducts)) {
                foreach ($stockrequestProducts as $pId => $enCoded) {
                    $codeArr = array();
                    Mage::helper('inventoryplus')->parseStr(Mage::helper('inventoryplus')->base64Decode($enCoded), $codeArr);
                    if (is_numeric($codeArr['qty_request']) && $codeArr['qty_request'] > 0) {
                        $checkProduct = 1;
                        $next = true;
                        break;
                    }
                }
            }
        }
        echo $checkProduct;
    }

    public function getImportCsvAction()
    {
        $fileToUpload = Mage::helper('inventoryplus')->getUploadFile();
        if (isset($fileToUpload['fileToUpload']['name']) && $fileToUpload['fileToUpload']['name'] != '') {
            try {
                Mage::getModel('admin/session')->setData('request_stock_reason', null);
                if ($this->getRequest()->getParam('reason')) {
                    Mage::getModel('admin/session')->setData('request_stock_reason', $this->getRequest()->getParam('reason'));
                }
                $fileName = $fileToUpload['fileToUpload']['tmp_name'];
                $Object = new Varien_File_Csv();
                $dataFile = $Object->getData($fileName);
                $requeststockProduct = array();
                $requeststockProducts = array();
                $fields = array();
                $count = 0;
                if (!empty($dataFile)) {
                    $productSku = array();
                    foreach ($dataFile as $col => $row) {
                        if ($col == 0) {
                            if (!empty($row))
                                foreach ($row as $index => $cell)
                                    $fields[$index] = (string)$cell;
                        } elseif ($col > 0) {
                            if (!empty($row))
                                foreach ($row as $index => $cell) {

                                    if (isset($fields[$index])) {
                                        $requeststockProduct[$fields[$index]] = $cell;
                                    }
                                }
                            $source = $this->getRequest()->getParam('source');
                            if ($source != 'others') {
                                $product = Mage::getResourceModel('catalog/product_collection')
                                    ->addAttributeToFilter('sku', $requeststockProduct['SKU'])
                                    ->setPageSize(1)
                                    ->setCurPage(1)
                                    ->getFirstItem();
                                if ($product->getId()) {
                                    $productId = $product->getId();
                                } else {
                                    continue;
                                }
                                $warehouseproduct = Mage::getModel('inventoryplus/warehouse_product')
                                    ->getCollection()
                                    ->addFieldToFilter('warehouse_id', $source)
                                    ->addFieldToFilter('product_id', $productId);
                                if ($warehouseproduct->getSize()) {
                                    $requeststockProducts[] = $requeststockProduct;
                                }
                            } else
                                $requeststockProducts[] = $requeststockProduct;
                        }
                    }
                }
                Mage::getModel('admin/session')->setData('requeststock_product_import', null);
                Mage::getModel('admin/session')->setData('null_requeststock_product_import', null);
                if (!empty($requeststockProducts)) {
                    Mage::getModel('admin/session')->setData('requeststock_product_import', $requeststockProducts);
                } else {
                    Mage::getModel('admin/session')->setData('null_requeststock_product_import', 1);
                }
            } catch (Exception $e) {
                Mage::log($e->getMessage(), null, 'inventory_warehouse.log');
            }
        }
    }

    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            if (isset($data['warehouse_source']) && $data['warehouse_source'] != 'others') {
                $data['warehouse_id_from'] = $data['warehouse_source'];
            }
            if (isset($data['warehouse_target'])) {
                $data['warehouse_id_to'] = $data['warehouse_target'];
            }
            $warehourseTarget = $warehourseSource = '';

            if (isset($data['warehouse_id_from']))
                $warehourseSource = Mage::getModel('inventoryplus/warehouse')->load($data['warehouse_id_from']);
            $warehourseTarget = Mage::getModel('inventoryplus/warehouse')->load($data['warehouse_id_to']);
            if ($warehourseSource && $warehourseSource->getWarehouseName() && $data['warehouse_source'] != 'others')
                $data['warehouse_name_from'] = $warehourseSource->getWarehouseName();
            elseif ($data['warehouse_source'] == 'others')
                $data['warehouse_name_from'] = Mage::helper('inventorywarehouse')->__('Others');
            if ($warehourseTarget->getWarehouseName())
                $data['warehouse_name_to'] = $warehourseTarget->getWarehouseName();
            $admin = Mage::getModel('admin/session')->getUser()->getUsername();
            $data['created_by'] = $admin;
            $now = now();
            $data['created_at'] = $now;
            //create send transaction data
            $transactionSendModel = Mage::getModel('inventorywarehouse/transaction');
            $transactionSendData = array();
            if ($data['warehouse_source'] == 'others') {
                $transactionSendData['type'] = '1';
            } else {
                $transactionSendData['type'] = '7';
            }

            $transactionSendData['warehouse_id_from'] = $transactionSendData['warehouse_name_from'] = $transactionSendData['warehouse_id_to'] = $transactionSendData['warehouse_name_to'] = '';
            if (isset($data['warehouse_id_from']))
                $transactionSendData['warehouse_id_from'] = $data['warehouse_id_from'];
            if (isset($data['warehouse_name_from']))
                $transactionSendData['warehouse_name_from'] = $data['warehouse_name_from'];
            $transactionSendData['warehouse_id_to'] = $data['warehouse_id_to'];
            $transactionSendData['warehouse_name_to'] = $data['warehouse_name_to'];
            $transactionSendData['created_at'] = $data['created_at'];
            $transactionSendData['created_by'] = $data['created_by'];
            $transactionSendData['reason'] = $data['reason'];
            $transactionSendModel->addData($transactionSendData);
            //create receive transaction data
            $transactionReceiveData['warehouse_id_from'] = $transactionReceiveData['warehouse_name_from'] = $transactionReceiveData['warehouse_id_to'] = $transactionReceiveData['warehouse_name_to'] = '';
            $transactionReceiveModel = Mage::getModel('inventorywarehouse/transaction');
            $transactionReceiveData = array();
            if ($data['warehouse_source'] == 'others') {
                $transactionReceiveData['type'] = '2';
            } else {
                $transactionReceiveData['type'] = '8';
            }

            if (isset($data['warehouse_id_from']))
                $transactionReceiveData['warehouse_id_from'] = $data['warehouse_id_from'];
            if (isset($data['warehouse_name_from']))
                $transactionReceiveData['warehouse_name_from'] = $data['warehouse_name_from'];
            $transactionReceiveData['warehouse_id_to'] = $data['warehouse_id_to'];
            $transactionReceiveData['warehouse_name_to'] = $data['warehouse_name_to'];
            $transactionReceiveData['created_at'] = $data['created_at'];
            $transactionReceiveData['created_by'] = $data['created_by'];
            $transactionReceiveData['reason'] = $data['reason'];
            $transactionReceiveModel->addData($transactionReceiveData);
            $data['status'] = 1;
            if ($data['warehouse_source'] != 'others')
                $data['status'] = 3;
            try {
                if ($this->getRequest()->getParam('id')) {
                    $model = Mage::getModel('inventorywarehouse/requeststock')->load($this->getRequest()->getParam('id'));
                    $model->addData($data);
                    $model->save();
                } else {
                    $model = Mage::getModel('inventorywarehouse/requeststock');
                    $model->setData($data);
                    $model->save();
                }
                if (isset($data['warehouse_id_from']))
                    $transactionSendModel->setRequestStockId($model->getId())
                        ->save();
                else
                    $transactionSendModel->save();
                $transactionReceiveModel->save();
                //save product
                if (isset($data['requeststock_products'])) {
                    $requeststockProducts = array();
                    Mage::helper('inventoryplus')->parseStr(urldecode($data['requeststock_products']), $requeststockProducts);
                    $total = array();
                    $notReceive = array();
                    $source = $target = '';
                    if (isset($data['warehouse_id_from']))
                        $source = $data['warehouse_id_from'];
                    $target = $data['warehouse_id_to'];
                    if (!empty($requeststockProducts)) {
                        foreach ($requeststockProducts as $pId => $enCoded) {
                            $codeArr = array();
                            $qty = 0;
                            $product = Mage::getModel('catalog/product')->load($pId);
                            Mage::helper('inventoryplus')->parseStr(Mage::helper('inventoryplus')->base64Decode($enCoded), $codeArr);

                            /*save in requeststock product*/
                            $requeststockProductsItem = Mage::getModel('inventorywarehouse/requeststock_product')
                                ->getCollection()
                                ->addFieldToFilter('warehouse_requeststock_id', $model->getId())
                                ->addFieldToFilter('product_id', $pId)
                                ->setPageSize(1)
                                ->setCurPage(1)
                                ->getFirstItem();
                            if ($requeststockProductsItem->getId()) {
                                if ($codeArr['qty_receive']) {
                                    if (!is_numeric($codeArr['qty_receive']) || $codeArr['qty_receive'] < 0)
                                        continue;
                                    $qty = (int)$codeArr['qty_receive'];
                                } elseif ($codeArr['qty_transfer']) {
                                    if (!is_numeric($codeArr['qty_transfer']) || $codeArr['qty_transfer'] < 0)
                                        continue;
                                    $qty = (int)$codeArr['qty_transfer'];
                                }
                                $requeststockProductsItem
                                    // ->setProductId($pId)
                                    ->setQtyReceive($qty)
                                    ->save();
                                array_push($total, (int)$qty);
                            } else {
                                $qty = $codeArr['qty_request'];
                                if ($source) {
                                    $warehouse = Mage::getModel('inventoryplus/warehouse_product')
                                        ->getCollection()
                                        ->addFieldToFilter('warehouse_id', $source)
                                        ->addFieldToFilter('product_id', $pId)
                                        ->getFirstItem();

                                    /** if source warhouse has not this product */
                                    if (!$warehouse->getId())
                                        continue;
                                    if (!is_numeric($codeArr['qty_request']) || (int)$codeArr['qty_request'] < 0)
                                        $codeArr['qty_request'] = 0;
                                    elseif ((int)$codeArr['qty_request'] <= (int)$warehouse->getTotalQty())
                                        $qty = (int)$codeArr['qty_request'];
                                    elseif ((int)$codeArr['qty_request'] > (int)$warehouse->getTotalQty() && $data['warehouse_source'] != 'others')
                                        $qty = (int)$warehouse->getTotalQty();
                                    elseif ((int)$codeArr['qty_request'] > (int)$warehouse->getTotalQty() && $data['warehouse_source'] == 'others')
                                        $qty = $codeArr['qty_request'];
                                }
                                Mage::getModel('inventorywarehouse/requeststock_product')
                                    ->setProductId($pId)
                                    ->setWarehouseRequeststockId($model->getId())
                                    ->setProductSku($product->getSku())
                                    ->setProductName($product->getName())
                                    ->setQty($qty)
                                    ->save();
                                $warehouseProductTarget = Mage::getModel('inventoryplus/warehouse_product')
                                    ->getCollection()
                                    ->addFieldToFilter('warehouse_id', $target)
                                    ->addFieldToFilter('product_id', $pId)
                                    ->setPageSize(1)
                                    ->setCurPage(1)
                                    ->getFirstItem();
                                if ($data['warehouse_source'] == 'others') {

                                    if ($warehouseProductTarget && $warehouseProductTarget->getId()) {
                                        $qtyTarget = $warehouseProductTarget->getTotalQty() + $qty;
                                        $qtyAvailableTarget = $warehouseProductTarget->getAvailableQty() + $qty;
                                        $warehouseProductTarget->setTotalQty($qtyTarget)
                                            ->setAvailableQty($qtyAvailableTarget)
                                            ->save();
                                    } else {
                                        /*save warehouse target*/
                                        Mage::getModel('inventoryplus/warehouse_product')
                                            ->setWarehouseId($target)
                                            ->setProductId($pId)
                                            ->setTotalQty($qty)
                                            ->setAvailableQty($qty)
                                            ->save();
                                    }
                                }

                                if ($data['warehouse_source'] != 'others') {
                                    /*save warehouse source*/
//                                    $currentQty = (int) $warehouse->getTotalQty() - $qty;
//                                    $currentQtyAvailable = (int) $warehouse->getAvailableQty() - $qty;
//                                    $warehouse->setTotalQty($currentQty);
//                                    $warehouse->setAvailableQty($currentQtyAvailable);
//                                    $warehouse->save();
                                } else {
                                    $stock_item = Mage::getModel('cataloginventory/stock_item')
                                        ->getCollection()
                                        ->addFieldToFilter('product_id', $pId)
                                        ->setPageSize(1)
                                        ->setCurPage(1)
                                        ->getFirstItem();
                                    $stock_item_qty = $stock_item->getQty();
                                    $new_stock_qty = $stock_item_qty + $qty;
                                    $stock_item->setQty($new_stock_qty)->save();
                                }
                                //save products to transaction product table for send transaction
                                Mage::getModel('inventorywarehouse/transaction_product')
                                    ->setWarehouseTransactionId($transactionSendModel->getId())
                                    ->setProductId($pId)
                                    ->setProductSku($product->getSku())
                                    ->setProductName($product->getName())
                                    ->setQty(-$qty)
                                    ->save();
                                //save products to transaction product table for receive transaction
                                Mage::getModel('inventorywarehouse/transaction_product')
                                    ->setWarehouseTransactionId($transactionReceiveModel->getId())
                                    ->setProductId($pId)
                                    ->setProductSku($product->getSku())
                                    ->setProductName($product->getName())
                                    ->setQty($qty)
                                    ->save();
                                $qty_request = (int)$qty;
                                array_push($total, $qty_request);
                            }
                        }
                        $totalProducts = array_sum($total);
                        $model->setTotalProducts($totalProducts);
                        $model->save();
                        $transactionSendModel->setTotalProducts(-$totalProducts);
                        $transactionSendModel->save();
                        $transactionReceiveModel->setTotalProducts($totalProducts);
                        $transactionReceiveModel->save();
                    }
                    $store = Mage::app()->getStore();
                    try {
                        if (Mage::getStoreConfig('inventoryplus/transaction/transaction_notice', $store->getId())) {
                            $warehouseSource = Mage::getModel('inventoryplus/warehouse')->load($source);
                            if ($source && !$warehouseSource->getIsUnwarehouse()) {
                                $stockName = Mage::helper('inventorywarehouse')->__('Request Stock');
                                Mage::helper('inventorywarehouse/email')->sendSendstockEmail($warehouseSource, $model->getId(), 0, $stockName);
                            }
                        }
                    } catch (Exception $e) {
                        Mage::log($e->getMessage(), null, 'inventory_management.log');
                    }
                }
                /* Remove scan data if using barcode scanner */
                $this->removeScanData();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('inventorywarehouse')->__('Stock request was successfully created.')
                );
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('adminhtml/inw_requeststock/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('adminhtml/inw_requeststock/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('inventorywarehouse')->__('Unable to save')
            );
            $this->_redirect('*/*/');
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('inventorywarehouse')->__('Unable to save')
            );
            $this->_redirect('*/*/');
        }
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function cancelAction()
    {
        $id = $this->getRequest()->getParam('id');
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('inventorywarehouse')->__('Unable to cancel (demo)')
        );
        $this->_redirect('adminhtml/inw_requeststock/edit', array('id' => $this->getRequest()->getParam('id')));
        return;
        $model = Mage::getModel('inventorywarehouse/requeststock')->load($id);
        $send_warehouse = $model->getWarehouseIdTo();
        $receive_warehouse = $model->getWarehouseIdFrom(); //zend_debug::dump($receive_warehouse.','.$send_warehouse);die();
        try {
            //change status of send stock record
            $model->setStatus(2)->save();
            //create send transaction
            $transactionSendModel = Mage::getModel('inventorywarehouse/transaction');
            $transactionSendData = array();
            $transactionSendData['type'] = '1';
            $transactionSendData['warehouse_id_from'] = $model->getWarehouseIdTo();
            $transactionSendData['warehousename_name'] = $model->getWarehouseNameTo();
            $transactionSendData['warehouse_id_to'] = $model->getWarehouseIdFrom();
            $transactionSendData['warehouse_name_to'] = $model->getWarehouseNameFrom();
            $transactionSendData['created_at'] = $model->getCreatedAt();
            $transactionSendData['created_by'] = $model->getCreatedBy();
            $transactionSendData['reason'] = Mage::helper('inventorywarehouse')->__("Cancel Stock Requesting No.'%s'" . $id);
            $transactionSendData['total_products'] = -$model->getTotalProducts();
            $transactionSendModel->addData($transactionSendData);
            $transactionSendModel->save();
            //create receive transaction
            $transactionReceiveModel = Mage::getModel('inventorywarehouse/transaction');
            $transactionReceiveData = array();
            $transactionReceiveData['type'] = '2';
            $transactionReceiveData['warehouse_id_from'] = $model->getWarehouseIdTo();
            $transactionReceiveData['warehouse_name_from'] = $model->getWarehouseNameTo();
            $transactionReceiveData['warehouse_id_to'] = $model->getWarehouseIdFrom();
            $transactionReceiveData['warehouse_name_to'] = $model->getWarehouseNameFrom();
            $transactionReceiveData['created_at'] = $model->getCreatedAt();
            $transactionReceiveData['created_by'] = $model->getCreatedBy();
            $transactionReceiveData['reason'] = Mage::helper('inventoryplus')->__("Cancel Stock Requesting No.'%s'" . $id);
            $transactionReceiveData['total_products'] = $model->getTotalProducts();
            $transactionReceiveModel->addData($transactionReceiveData);
            $transactionReceiveModel->save();

            //recalculate qty
            $requeststockProducts = Mage::getModel('inventorywarehouse/requeststock_product')
                ->getCollection()
                ->addFieldToFilter('warehouse_requeststock_id', $id);
            foreach ($requeststockProducts as $requeststockProduct) {
                $pId = $requeststockProduct->getProductId();
                $pSku = $requeststockProduct->getProductSku();
                $pName = $requeststockProduct->getProductName();
                //get qty of product using for transaction                
                $qty = $requeststockProduct->getQty();
                //save products to transaction product table for send transaction
                Mage::getModel('inventorywarehouse/transaction_product')
                    ->setWarehouseTransactionId($transactionSendModel->getId())
                    ->setProductId($pId)
                    ->setProductSku($pSku)
                    ->setProductName($pName)
                    ->setQty(-$qty)
                    ->save();
                //save products to transaction product table for receive transaction
                Mage::getModel('inventorywarehouse/transaction_product')
                    ->setWarehouseTransactionId($transactionReceiveModel->getId())
                    ->setProductId($pId)
                    ->setProductSku($pSku)
                    ->setProductName($pName)
                    ->setQty($qty)
                    ->save();
                //recalculate product qty for warehouse send
                if ($send_warehouse != 0) {
                    $send_warehouse_products = Mage::getModel('inventoryplus/warehouse_product')
                        ->getCollection()
                        ->addFieldToFilter('warehouse_id', $send_warehouse)
                        ->addFieldToFilter('product_id', $pId)
                        ->setPageSize(1)
                        ->setCurPage(1)
                        ->getFirstItem();
                    $newProductsQtySend = $send_warehouse_products->getTotalQty() - $qty;
                    $newProductsQtyAvailableSend = $send_warehouse_products->getAvailableQty() - $qty;
                    if ($newProductsQtySend == 0 && $newProductsQtyAvailableSend == 0) {
                        $send_warehouse_products->delete();
                    } else {
                        $send_warehouse_products
                            ->setTotalQty($newProductsQtySend)
                            ->setAvailableQty($newProductsQtyAvailableSend)
                            ->save();
                    }
                    $store = Mage::app()->getStore();
                    try {
                        if (Mage::getStoreConfig('inventoryplus/transaction/transaction_notice', $store->getId())) {
                            $warehouseTaget = Mage::getModel('inventoryplus/warehouse')->load($send_warehouse);
                            if ($send_warehouse && !$warehouseTaget->getIsUnwarehouse()) {
                                $stockName = "Cancel Request Stock #" . $id;
                                Mage::helper('inventorywarehouse/email')->sendSendstockEmail($warehouseTaget, $id, 0, $stockName);
                            }
                        }
                    } catch (Exception $e) {
                        Mage::log($e->getMessage(), null, 'inventory_management.log');
                    }
                }
                //recalculate product qty for warehouses receive
                if ($receive_warehouse) {
                    $receive_warehouse_products = Mage::getModel('inventoryplus/warehouse_product')
                        ->getCollection()
                        ->addFieldToFilter('warehouse_id', $receive_warehouse)
                        ->addFieldToFilter('product_id', $pId)
                        ->setPageSize(1)
                        ->setCurPage(1)
                        ->getFirstItem();
                    $newProductsQtyReceive = $receive_warehouse_products->getTotalQty() + $qty;
                    $newProductsQtyAvailableReceive = $receive_warehouse_products->getAvailableQty() + $qty;
                    if (!$receive_warehouse_products->getId()) {
                        $receive_warehouse_products = Mage::getModel('inventoryplus/warehouse_product')
                            ->setData('warehouse_id', $receive_warehouse)
                            ->setData('product_id', $pId)
                            ->setData('available_qty', $qty)
                            ->setData('total_qty', $qty)
                            ->save();
                    } else {
                        $receive_warehouse_products
                            ->setTotalQty($newProductsQtyReceive)
                            ->setAvailableQty($newProductsQtyAvailableReceive)
                            ->save();
                    }
                } else {
                    $stock_item = Mage::getModel('cataloginventory/stock_item')
                        ->getCollection()
                        ->addFieldToFilter('product_id', $pId)
                        ->setPageSize(1)
                        ->setCurPage(1)
                        ->getFirstItem();
                    $stock_item_qty = $stock_item->getQty();
                    $new_stock_qty = $stock_item_qty - $qty;
                    $stock_item->setQty($new_stock_qty)->save();
                }
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('inventorywarehouse')->__('Stock request was successfully canceled.')
            );
            if ($this->getRequest()->getParam('warehouse_id'))
                $this->_redirect('adminhtml/inw_warehouse/edit', array('id' => $this->getRequest()->getParam('warehouse_id')));
            else
                $this->_redirect('adminhtml/inw_requeststock/index');
            return;
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            if ($this->getRequest()->getParam('warehouse_id'))
                $this->_redirect('adminhtml/inw_warehouse/edit', array('id' => $this->getRequest()->getParam('warehouse_id')));
            else
                $this->_redirect('adminhtml/inw_requeststock/edit', array('id' => $this->getRequest()->getParam('id')));
            return;
        }

        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('inventorywarehouse')->__('Unable to cancel')
        );
        if ($this->getRequest()->getParam('warehouse_id'))
            $this->_redirect('adminhtml/inw_warehouse/edit', array('id' => $this->getRequest()->getParam('warehouse_id')));
        else
            $this->_redirect('adminhtml/inw_requeststock/index');
    }

    /**
     * export grid item to CSV type
     */
    public function exportCsvAction()
    {
        $fileName = 'stock_request.csv';
        $content = $this->getLayout()
            ->createBlock('inventorywarehouse/adminhtml_requeststock_grid')
            ->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * export grid item to XML type
     */
    public function exportXmlAction()
    {
        $fileName = 'stock_request.xml';
        $content = $this->getLayout()
            ->createBlock('inventorywarehouse/adminhtml_requeststock_grid')
            ->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function exportpdfrequeststockAction()
    {
        try {
            $warehouse_requeststock_id = $this->getRequest()->getParam('id');
            $requeststockProducts = Mage::getModel('inventorywarehouse/requeststock_product')->getCollection()
                ->addFieldToFilter('warehouse_requeststock_id', $warehouse_requeststock_id)
                ->getData();
            $img = Mage::getDesign()->getSkinUrl('images/logo_email.gif', array('_area' => 'frontend'));
            $contents = '<div><img src="' . $img . '" /></div>';
            $contents .= $this->getLayout()->createBlock('inventorywarehouse/adminhtml_sendstock')
                ->setRequeststock($warehouse_requeststock_id)
                ->setRequeststockproducts($requeststockProducts)
                ->setTemplate('inventorywarehouse/requeststock/requeststock.phtml')
                ->toHtml();

            include("lib/MPDF56/mpdf.php");

            $mpdf = new mPDF();

            $mpdf->WriteHTML($contents);

            $fileName = 'request-stock-list-' . Mage::helper('core')->formatDate(now(), 'short') . '-' . $warehouse_requeststock_id;

            $mpdf->Output($fileName . '.pdf', 'D');
            exit;
        } catch (HTML2PDF_exception $e) {
            Mage::log($e->getMessage(), null, 'inventory_warehouse.log');
            exit;
        }
    }

    public function removeScanData()
    {
        if (!Mage::helper('core')->isModuleEnabled('Magestore_Inventorybarcode')) {
            return;
        }
        $action = Magestore_Inventorywarehouse_Block_Adminhtml_Requeststock_Scanbarcode::SCAN_ACTION;
        Mage::getModel('inventorybarcode/barcode_scanitem')->reset($action);
    }

    public function newDeliveryAction()
    {
        $purchaseOrderId = $this->getRequest()->getParam('requeststock_id');
//        $purchaseOrderId = 2;
        $model = Mage::getModel('inventorywarehouse/requeststock')->load($purchaseOrderId);
        $this->_title($this->__('Inventory'))
            ->_title($this->__('Add New Delivery'));
        if ($model->getId() || $purchaseOrderId == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }
            Mage::register('purchaseorder_data', $model);

            $this->loadLayout()->_setActiveMenu($this->_menu_path);

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock('inventorywarehouse/adminhtml_requeststock_editdelivery'))
                ->_addLeft($this->getLayout()->createBlock('inventorywarehouse/adminhtml_requeststock_editdelivery_tabs'));
            $this->renderLayout();

            if (Mage::getModel('admin/session')->getData('delivery_purchaseorder_product_import')) {
                Mage::getModel('admin/session')->setData('delivery_purchaseorder_product_import', null);
            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('inventorypurchasing')->__('Item does not exist')
            );
            $this->_redirect('*/*/');
        }
    }

    public function prepareDeliveryAction()
    {
        $this->_title($this->__('Inventory'))
            ->_title($this->__('Add New Delivery'));
        $this->loadLayout();
        $this->getLayout()->getBlock('inventorywarehouse.requeststock.edit.tab.preparedelivery')
            ->setProducts($this->getRequest()->getPost('in_products', null));

        $this->getLayout()->getBlock('grid_serializer')->addColumnInputName('qty_delivery');
        $this->renderLayout();
    }

    public function createalldeliveryAction(){
        if ($requeststock_id = Mage::app()->getRequest()->getParam('requeststock_id')) {
            $requeststock_id = Mage::app()->getRequest()->getParam('requeststock_id');
            $requeststock = Mage::getModel('inventorywarehouse/requeststock')->load($requeststock_id);
            if ($requeststock->getData('warehouse_id_from') != 0)
                $warehourseSource = Mage::getModel('inventoryplus/warehouse')->load($requeststock->getData('warehouse_id_from'));
            $warehourseTarget = Mage::getModel('inventoryplus/warehouse')->load($requeststock->getData('warehouse_id_to'));

            $admin = Mage::getModel('admin/session')->getUser()->getUsername();
            $now = now();

            //create send transaction data
            $transactionSendModel = Mage::getModel('inventorywarehouse/transaction');
            $transactionSendData = array();
            if ($requeststock->getData('warehouse_id_from') != 0) {
                $transactionSendData['type'] = '1';
            } else {
                $transactionSendData['type'] = '7';
            }

            $transactionSendData['warehouse_id_from'] = $transactionSendData['warehouse_name_from'] = $transactionSendData['warehouse_id_to'] = $transactionSendData['warehouse_name_to'] = '';
            $transactionSendData['warehouse_id_from'] = $requeststock->getData('warehouse_id_from');
            $transactionSendData['warehouse_name_from'] = $requeststock->getData('warehouse_name_from');
            $transactionSendData['warehouse_id_to'] = $requeststock->getData('warehouse_id_to');
            $transactionSendData['warehouse_name_to'] = $requeststock->getData('warehouse_name_to');
            $transactionSendData['created_at'] = $now;
            $transactionSendData['created_by'] = $admin;
            $transactionSendData['reason'] = $requeststock->getData('reason');
            $transactionSendModel->addData($transactionSendData);

            //create receive transaction data
            $transactionReceiveData['warehouse_id_from'] = $transactionReceiveData['warehouse_name_from'] = $transactionReceiveData['warehouse_id_to'] = $transactionReceiveData['warehouse_name_to'] = '';
            $transactionReceiveModel = Mage::getModel('inventorywarehouse/transaction');
            $transactionReceiveData = array();
            if ($requeststock->getData('warehouse_id_from') != 0) {
                $transactionReceiveData['type'] = '2';
            } else {
                $transactionReceiveData['type'] = '8';
            }
//
            $transactionReceiveData['warehouse_id_from'] = $requeststock->getData('warehouse_id_from');
            $transactionReceiveData['warehouse_name_from'] = $requeststock->getData('warehouse_name_from');
            $transactionReceiveData['warehouse_id_to'] = $requeststock->getData('warehouse_id_to');
            $transactionReceiveData['warehouse_name_to'] = $requeststock->getData('warehouse_name_to');
            $transactionReceiveData['created_at'] = $now;
            $transactionReceiveData['created_by'] = $admin;
            $transactionReceiveData['reason'] = $requeststock->getData('reason');
            $transactionReceiveModel->addData($transactionReceiveData);
            $transactionSendModel->save();
            $transactionReceiveModel->save();
            //save product
            $requeststockProducts = Mage::getModel('inventorywarehouse/requeststock_product')->getCollection()
                ->addFieldToFilter('warehouse_requeststock_id', $requeststock_id);
            $total = array();
            $notReceive = array();
            $source = $target = '';
            $source = $requeststock->getData('warehouse_id_from');
            $target = $requeststock->getData('warehouse_id_to');

            foreach ($requeststockProducts as $requeststockProduct){
                $qty_request = $requeststockProduct->getQty();
                $qty_delivery = $requeststockProduct->getTotalDelivery();
                if($qty_delivery == $qty_request)
                    continue;
                $pId = $requeststockProduct->getProductId();
                $product = Mage::getModel('catalog/product')->load($pId);

                /*save in requeststock product*/
                $requeststockProductsItem = Mage::getModel('inventorywarehouse/requeststock_product')
                    ->getCollection()
                    ->addFieldToFilter('warehouse_requeststock_id', $requeststock->getId())
                    ->addFieldToFilter('product_id', $pId)
                    ->setPageSize(1)
                    ->setCurPage(1)
                    ->getFirstItem();
                $qty_delivery = $qty_request - $qty_delivery;
                $qty_request = $requeststockProductsItem->getQty();
                $total_delivery = $requeststockProductsItem->getTotalDelivery();
                if ($qty_request >= ($total_delivery + $qty_delivery)) {
                    $qty = $qty_delivery;
                } else {
                    $qty = $qty_request - $total_delivery;
                }

                if ($source) {
                    $warehouse = Mage::getModel('inventoryplus/warehouse_product')
                        ->getCollection()
                        ->addFieldToFilter('warehouse_id', $source)
                        ->addFieldToFilter('product_id', $pId)
                        ->getFirstItem();

                    /** if source warhouse has not this product */
                    if (!$warehouse->getId())
                        continue;
                    if ((int)$qty > (int)$warehouse->getTotalQty())
                        $qty = (int)$warehouse->getTotalQty();
                }
                echo '$qty:' . $qty . '<br/>';
                $requeststockDelivery = Mage::getModel('inventorywarehouse/requeststockdelivery');
                $requeststockDelivery->setWarehouseRequeststockId($requeststock_id)
                    ->setTime($now)
                    ->setProductId($pId)
                    ->setProductName($product->getName())
                    ->setProductSku($product->getSku())
                    ->setQtyDelivery($qty)
                    ->setCreatedBy($admin)
                    ->save();
                $requeststockProductsItem->setTotalDelivery($total_delivery + $qty)->save();
                array_push($total, (int)$qty);

                $warehouseProductTarget = Mage::getModel('inventoryplus/warehouse_product')
                    ->getCollection()
                    ->addFieldToFilter('warehouse_id', $target)
                    ->addFieldToFilter('product_id', $pId)
                    ->setPageSize(1)
                    ->setCurPage(1)
                    ->getFirstItem();
                if ($warehouseProductTarget && $warehouseProductTarget->getId()) {
                    $qtyTarget = $warehouseProductTarget->getTotalQty() + $qty;
                    $qtyAvailableTarget = $warehouseProductTarget->getAvailableQty() + $qty;
                    $warehouseProductTarget->setTotalQty($qtyTarget)
                        ->setAvailableQty($qtyAvailableTarget)
                        ->save();
                } else {
                    /*save warehouse target*/
                    Mage::getModel('inventoryplus/warehouse_product')
                        ->setWarehouseId($target)
                        ->setProductId($pId)
                        ->setTotalQty($qty)
                        ->setAvailableQty($qty)
                        ->save();
                }
//
//                                    /*save warehouse source*/
                $currentQty = (int)$warehouse->getTotalQty() - $qty;
                $currentQtyAvailable = (int)$warehouse->getAvailableQty() - $qty;
                $warehouse->setTotalQty($currentQty);
                $warehouse->setAvailableQty($currentQtyAvailable);
                $warehouse->save();
//
                //save products to transaction product table for send transaction
                Mage::getModel('inventorywarehouse/transaction_product')
                    ->setWarehouseTransactionId($transactionSendModel->getId())
                    ->setProductId($pId)
                    ->setProductSku($product->getSku())
                    ->setProductName($product->getName())
                    ->setQty(-$qty)
                    ->save();
                //save products to transaction product table for receive transaction
                Mage::getModel('inventorywarehouse/transaction_product')
                    ->setWarehouseTransactionId($transactionReceiveModel->getId())
                    ->setProductId($pId)
                    ->setProductSku($product->getSku())
                    ->setProductName($product->getName())
                    ->setQty($qty)
                    ->save();
            }

            $totalProducts = array_sum($total);
            $transactionSendModel->setTotalProducts(-$totalProducts);
            $transactionSendModel->save();
            $transactionReceiveModel->setTotalProducts($totalProducts);
            $transactionReceiveModel->save();


            $requeststockProducts = Mage::getModel('inventorywarehouse/requeststock_product')->getCollection()
                ->addFieldToFilter('warehouse_requeststock_id', $requeststock_id);
            $complete = true;
            $processing = false;
            foreach ($requeststockProducts as $requeststockProduct){
                if($requeststockProduct->getQty() != $requeststockProduct->getTotalDelivery())
                    $complete = false;
                if($requeststockProduct->getTotalDelivery() != 0)
                    $processing = true;
            }

            if($processing)
                $requeststock->setStatus(4)->save();
            if ($complete)
                $requeststock->setStatus(1)->save();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('inventorywarehouse')->__('Delivery stock request was successfully created.')
            );

            $this->_redirect('adminhtml/inw_requeststock/edit', array('id' => $requeststock_id));
            return;

        }else{
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('inventorywarehouse')->__('Unable to save')
            );
            $this->_redirect('*/*/');
        }
    }

    public function saveDeliveryAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $requeststock_id = Mage::app()->getRequest()->getParam('id');
            $requeststock = Mage::getModel('inventorywarehouse/requeststock')->load($requeststock_id);
            if ($requeststock->getData('warehouse_id_from') != 0)
                $warehourseSource = Mage::getModel('inventoryplus/warehouse')->load($requeststock->getData('warehouse_id_from'));
            $warehourseTarget = Mage::getModel('inventoryplus/warehouse')->load($requeststock->getData('warehouse_id_to'));

            $admin = Mage::getModel('admin/session')->getUser()->getUsername();
            $now = now();

            //create send transaction data
            $transactionSendModel = Mage::getModel('inventorywarehouse/transaction');
            $transactionSendData = array();
            if ($requeststock->getData('warehouse_id_from') != 0) {
                $transactionSendData['type'] = '1';
            } else {
                $transactionSendData['type'] = '7';
            }

            $transactionSendData['warehouse_id_from'] = $transactionSendData['warehouse_name_from'] = $transactionSendData['warehouse_id_to'] = $transactionSendData['warehouse_name_to'] = '';
            $transactionSendData['warehouse_id_from'] = $requeststock->getData('warehouse_id_from');
            $transactionSendData['warehouse_name_from'] = $requeststock->getData('warehouse_name_from');
            $transactionSendData['warehouse_id_to'] = $requeststock->getData('warehouse_id_to');
            $transactionSendData['warehouse_name_to'] = $requeststock->getData('warehouse_name_to');
            $transactionSendData['created_at'] = $now;
            $transactionSendData['created_by'] = $admin;
            $transactionSendData['reason'] = $requeststock->getData('reason');
            $transactionSendModel->addData($transactionSendData);

            //create receive transaction data
            $transactionReceiveData['warehouse_id_from'] = $transactionReceiveData['warehouse_name_from'] = $transactionReceiveData['warehouse_id_to'] = $transactionReceiveData['warehouse_name_to'] = '';
            $transactionReceiveModel = Mage::getModel('inventorywarehouse/transaction');
            $transactionReceiveData = array();
            if ($requeststock->getData('warehouse_id_from') != 0) {
                $transactionReceiveData['type'] = '2';
            } else {
                $transactionReceiveData['type'] = '8';
            }
//
            $transactionReceiveData['warehouse_id_from'] = $requeststock->getData('warehouse_id_from');
            $transactionReceiveData['warehouse_name_from'] = $requeststock->getData('warehouse_name_from');
            $transactionReceiveData['warehouse_id_to'] = $requeststock->getData('warehouse_id_to');
            $transactionReceiveData['warehouse_name_to'] = $requeststock->getData('warehouse_name_to');
            $transactionReceiveData['created_at'] = $now;
            $transactionReceiveData['created_by'] = $admin;
            $transactionReceiveData['reason'] = $requeststock->getData('reason');
            $transactionReceiveModel->addData($transactionReceiveData);
            $transactionSendModel->save();
            $transactionReceiveModel->save();
            //save product
            if (isset($data['product_delivery'])) {
                $requeststockProducts = array();
                Mage::helper('inventoryplus')->parseStr(urldecode($data['product_delivery']), $requeststockProducts);
                $total = array();
                $notReceive = array();
                $source = $target = '';
                $source = $requeststock->getData('warehouse_id_from');
                $target = $requeststock->getData('warehouse_id_to');
                if (!empty($requeststockProducts)) {
                    foreach ($requeststockProducts as $pId => $enCoded) {
                        $codeArr = array();
                        $qty = 0;
                        $product = Mage::getModel('catalog/product')->load($pId);
                        Mage::helper('inventoryplus')->parseStr(Mage::helper('inventoryplus')->base64Decode($enCoded), $codeArr);
//
                        /*save in requeststock product*/
                        echo $product->getName() . '<br/>';
                        echo $product->getSku() . '<br/>';
                        $requeststockProductsItem = Mage::getModel('inventorywarehouse/requeststock_product')
                            ->getCollection()
                            ->addFieldToFilter('warehouse_requeststock_id', $requeststock->getId())
                            ->addFieldToFilter('product_id', $pId)
                            ->setPageSize(1)
                            ->setCurPage(1)
                            ->getFirstItem();
                        $qty_delivery = $codeArr['qty_delivery'];
                        $qty_request = $requeststockProductsItem->getQty();
                        $total_delivery = $requeststockProductsItem->getTotalDelivery();
                        echo '$qty_delivery: ' . $qty_delivery . '<br/>';
                        echo '$qty_request: ' . $qty_request . '<br/>';
                        echo '$total_qty: ' . $total_delivery . '<br/>';
                        if ($qty_request >= ($total_delivery + $qty_delivery)) {
                            $qty = $qty_delivery;
                        } else {
                            $qty = $qty_request - $total_delivery;
                        }

                        if ($source) {
                            $warehouse = Mage::getModel('inventoryplus/warehouse_product')
                                ->getCollection()
                                ->addFieldToFilter('warehouse_id', $source)
                                ->addFieldToFilter('product_id', $pId)
                                ->getFirstItem();

                            /** if source warhouse has not this product */
                            if (!$warehouse->getId())
                                continue;
                         if ((int)$qty > (int)$warehouse->getTotalQty())
                                $qty = (int)$warehouse->getTotalQty();
                        }
                        echo '$qty:' . $qty . '<br/>';
                        $requeststockDelivery = Mage::getModel('inventorywarehouse/requeststockdelivery');
                        $requeststockDelivery->setWarehouseRequeststockId($requeststock_id)
                            ->setTime($now)
                            ->setProductId($pId)
                            ->setProductName($product->getName())
                            ->setProductSku($product->getSku())
                            ->setQtyDelivery($qty)
                            ->setCreatedBy($admin)
                            ->save();
                        $requeststockProductsItem->setTotalDelivery($total_delivery + $qty)->save();
                        array_push($total, (int)$qty);

                        $warehouseProductTarget = Mage::getModel('inventoryplus/warehouse_product')
                            ->getCollection()
                            ->addFieldToFilter('warehouse_id', $target)
                            ->addFieldToFilter('product_id', $pId)
                            ->setPageSize(1)
                            ->setCurPage(1)
                            ->getFirstItem();
                        if ($warehouseProductTarget && $warehouseProductTarget->getId()) {
                            $qtyTarget = $warehouseProductTarget->getTotalQty() + $qty;
                            $qtyAvailableTarget = $warehouseProductTarget->getAvailableQty() + $qty;
                            $warehouseProductTarget->setTotalQty($qtyTarget)
                                ->setAvailableQty($qtyAvailableTarget)
                                ->save();
                        } else {
                            /*save warehouse target*/
                            Mage::getModel('inventoryplus/warehouse_product')
                                ->setWarehouseId($target)
                                ->setProductId($pId)
                                ->setTotalQty($qty)
                                ->setAvailableQty($qty)
                                ->save();
                        }
//
//                                    /*save warehouse source*/
                        $currentQty = (int)$warehouse->getTotalQty() - $qty;
                        $currentQtyAvailable = (int)$warehouse->getAvailableQty() - $qty;
                        $warehouse->setTotalQty($currentQty);
                        $warehouse->setAvailableQty($currentQtyAvailable);
                        $warehouse->save();
//
                        //save products to transaction product table for send transaction
                        Mage::getModel('inventorywarehouse/transaction_product')
                            ->setWarehouseTransactionId($transactionSendModel->getId())
                            ->setProductId($pId)
                            ->setProductSku($product->getSku())
                            ->setProductName($product->getName())
                            ->setQty(-$qty)
                            ->save();
                        //save products to transaction product table for receive transaction
                        Mage::getModel('inventorywarehouse/transaction_product')
                            ->setWarehouseTransactionId($transactionReceiveModel->getId())
                            ->setProductId($pId)
                            ->setProductSku($product->getSku())
                            ->setProductName($product->getName())
                            ->setQty($qty)
                            ->save();


                    }

                    $totalProducts = array_sum($total);
                    $transactionSendModel->setTotalProducts(-$totalProducts);
                    $transactionSendModel->save();
                    $transactionReceiveModel->setTotalProducts($totalProducts);
                    $transactionReceiveModel->save();

                    $requeststockProducts = Mage::getModel('inventorywarehouse/requeststock_product')->getCollection()
                        ->addFieldToFilter('warehouse_requeststock_id', $requeststock_id);
                    $complete = true;
                    $processing = false;
                    Zend_Debug::dump($requeststockProducts->getData());
                    foreach ($requeststockProducts as $requeststockProduct){
                        if($requeststockProduct->getQty() != $requeststockProduct->getTotalDelivery())
                            $complete = false;
                        if($requeststockProduct->getTotalDelivery() != 0)
                            $processing = true;
                    }

                    if($processing)
                        $requeststock->setStatus(4)->save();
                    if ($complete)
                        $requeststock->setStatus(1)->save();
                    Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('inventorywarehouse')->__('Delivery stock request was successfully created.')
                    );

                    $this->_redirect('adminhtml/inw_requeststock/edit', array('id' => $requeststock_id));
                    return;
                }
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('inventorywarehouse')->__('Unable to save')
                );
                $this->_redirect('*/*/');
            } else {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('inventorywarehouse')->__('Unable to save')
                );
                $this->_redirect('*/*/');
            }
        }
    }
}
