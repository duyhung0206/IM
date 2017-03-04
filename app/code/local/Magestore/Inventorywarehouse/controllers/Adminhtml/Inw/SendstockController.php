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
class Magestore_Inventorywarehouse_Adminhtml_Inw_SendstockController 
    extends Magestore_Inventoryplus_Controller_Action 
    implements Magestore_Inventoryplus_Controller_Scan {

    public function productsdeliveryAction()
    {
        $this->loadLayout();
        $this->renderLayout();
//        $this->renderLayout();
//        if (Mage::getModel('admin/session')->getData('requeststock_product_import'))
//            Mage::getModel('admin/session')->setData('requeststock_product_import', null);
    }

    public function newDeliveryAction()
    {
        $sendstock_id = $this->getRequest()->getParam('sendstock_id');
//        $purchaseOrderId = 2;
        $model = Mage::getModel('inventorywarehouse/sendstock')->load($sendstock_id);
        $this->_title($this->__('Inventory'))
            ->_title($this->__('Add New Delivery'));
        if ($model->getId() || $sendstock_id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }
            Mage::register('sendstock_data', $model);

            $this->loadLayout()->_setActiveMenu($this->_menu_path);

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock('inventorywarehouse/adminhtml_sendstock_editdelivery'))
                ->_addLeft($this->getLayout()->createBlock('inventorywarehouse/adminhtml_sendstock_editdelivery_tabs'));
            $this->renderLayout();

//            if (Mage::getModel('admin/session')->getData('delivery_purchaseorder_product_import')) {
//                Mage::getModel('admin/session')->setData('delivery_purchaseorder_product_import', null);
//            }
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('inventorywarehouse')->__('Item does not exist')
            );
            $this->_redirect('*/*/');
        }
    }

    public function prepareDeliveryAction()
    {
        $this->_title($this->__('Inventory'))
            ->_title($this->__('Add New Delivery'));
        $this->loadLayout();
        $this->getLayout()->getBlock('inventorywarehouse.sendstock.edit.tab.preparedelivery')
            ->setProducts($this->getRequest()->getPost('in_products', null));

        $this->getLayout()->getBlock('grid_serializer')->addColumnInputName('qty_delivery');
        $this->renderLayout();
    }

    public function saveDeliveryAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $sendstock_id = Mage::app()->getRequest()->getParam('id');
            $sendstock = Mage::getModel('inventorywarehouse/sendstock')->load($sendstock_id);
            if ($sendstock->getData('warehouse_id_from') != 0)
                $warehourseSource = Mage::getModel('inventoryplus/warehouse')->load($sendstock->getData('warehouse_id_from'));
                $warehourseTarget = Mage::getModel('inventoryplus/warehouse')->load($sendstock->getData('warehouse_id_to'));

            $admin = Mage::getModel('admin/session')->getUser()->getUsername();
            $now = now();

            //create send transaction data
            $transactionSendModel = Mage::getModel('inventorywarehouse/transaction');
            $transactionSendData = array();
            if ($sendstock->getData('warehouse_id_from') != 0) {
                $transactionSendData['type'] = '1';
            } else {
                $transactionSendData['type'] = '7';
            }

            $transactionSendData['warehouse_id_from'] = $transactionSendData['warehouse_name_from'] = $transactionSendData['warehouse_id_to'] = $transactionSendData['warehouse_name_to'] = '';
            $transactionSendData['warehouse_id_from'] = $sendstock->getData('warehouse_id_from');
            $transactionSendData['warehouse_name_from'] = $sendstock->getData('warehouse_name_from');
            $transactionSendData['warehouse_id_to'] = $sendstock->getData('warehouse_id_to');
            $transactionSendData['warehouse_name_to'] = $sendstock->getData('warehouse_name_to');
            $transactionSendData['created_at'] = $now;
            $transactionSendData['created_by'] = $admin;
            $transactionSendData['reason'] = $sendstock->getData('reason');
            $transactionSendModel->addData($transactionSendData);

            //create receive transaction data
            $transactionReceiveData['warehouse_id_from'] = $transactionReceiveData['warehouse_name_from'] = $transactionReceiveData['warehouse_id_to'] = $transactionReceiveData['warehouse_name_to'] = '';
            $transactionReceiveModel = Mage::getModel('inventorywarehouse/transaction');
            $transactionReceiveData = array();
            if ($sendstock->getData('warehouse_id_from') != 0) {
                $transactionReceiveData['type'] = '2';
            } else {
                $transactionReceiveData['type'] = '8';
            }
//
            $transactionReceiveData['warehouse_id_from'] = $sendstock->getData('warehouse_id_from');
            $transactionReceiveData['warehouse_name_from'] = $sendstock->getData('warehouse_name_from');
            $transactionReceiveData['warehouse_id_to'] = $sendstock->getData('warehouse_id_to');
            $transactionReceiveData['warehouse_name_to'] = $sendstock->getData('warehouse_name_to');
            $transactionReceiveData['created_at'] = $now;
            $transactionReceiveData['created_by'] = $admin;
            $transactionReceiveData['reason'] = $sendstock->getData('reason');
            $transactionReceiveModel->addData($transactionReceiveData);
            $transactionSendModel->save();
            $transactionReceiveModel->save();
            //save product
            if (isset($data['product_delivery'])) {
                $sendstockProducts = array();
                Mage::helper('inventoryplus')->parseStr(urldecode($data['product_delivery']), $sendstockProducts);
                $total = array();
                $notReceive = array();
                $source = $target = '';
                $source = $sendstock->getData('warehouse_id_from');
                $target = $sendstock->getData('warehouse_id_to');
                if (!empty($sendstockProducts)) {
                    foreach ($sendstockProducts as $pId => $enCoded) {
                        $codeArr = array();
                        $qty = 0;
                        $product = Mage::getModel('catalog/product')->load($pId);
                        Mage::helper('inventoryplus')->parseStr(Mage::helper('inventoryplus')->base64Decode($enCoded), $codeArr);
//
                        /*save in sendstock product*/
//                        echo $product->getName() . '<br/>';
//                        echo $product->getSku() . '<br/>';
                        $sendstockProductsItem = Mage::getModel('inventorywarehouse/sendstock_product')
                            ->getCollection()
                            ->addFieldToFilter('warehouse_sendstock_id', $sendstock->getId())
                            ->addFieldToFilter('product_id', $pId)
                            ->setPageSize(1)
                            ->setCurPage(1)
                            ->getFirstItem();
                        $qty_delivery = $codeArr['qty_delivery'];
                        $qty_send = abs($sendstockProductsItem->getQty());
                        $total_delivery = abs($sendstockProductsItem->getTotalDelivery());

//                        die();
                        if ($qty_send >= ($total_delivery + $qty_delivery)) {
                            $qty = $qty_delivery;
                        } else {
                            $qty = $qty_send - $total_delivery;
                        }
//                        echo '$qty_delivery: ' . $qty_delivery . '<br/>';
//                        echo '$qty_send: ' . $qty_send . '<br/>';
//                        echo '$total_qty: ' . $total_delivery . '<br/>';
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
//                        echo '$qty:' . $qty . '<br/>';
//                        echo '$qty_delivery: ' . $qty_delivery . '<br/>';
//                        echo '$qty_send: ' . $qty_send . '<br/>';
//                        echo '$total_qty: ' . $total_delivery . '<br/>';
//                        die();
                        $sendstockProductsItem->setTotalDelivery($total_delivery + $qty)->save();
                        $sendstockDelivery = Mage::getModel('inventorywarehouse/sendstockdelivery');
                        $sendstockDelivery->setWarehouseSendstockId($sendstock_id)
                            ->setTime($now)
                            ->setProductId($pId)
                            ->setProductName($product->getName())
                            ->setProductSku($product->getSku())
                            ->setQtyDelivery($qty)
                            ->setCreatedBy($admin)
                            ->save();

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

                    $sendstockProducts = Mage::getModel('inventorywarehouse/sendstock_product')->getCollection()
                        ->addFieldToFilter('warehouse_sendstock_id', $sendstock_id);
                    $complete = true;
                    $processing = false;
                    Zend_Debug::dump($sendstockProducts->getData());
                    $total_delivery = 0;
                    foreach ($sendstockProducts as $sendstockProduct){
                        if(abs($sendstockProduct->getQty()) != abs($sendstockProduct->getTotalDelivery()))
                            $complete = false;
                        if($sendstockProduct->getTotalDelivery() != 0)
                            $processing = true;
                    }

                    if($processing)
                        $sendstock->setStatus(4)->save();
                    if ($complete)
                        $sendstock->setStatus(1)->save();
                    Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('inventorywarehouse')->__('Delivery stock send was successfully created.')
                    );

                    $this->_redirect('adminhtml/inw_sendstock/edit', array('id' => $sendstock_id));
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
    /**
     * Menu Path
     * 
     * @var string 
     */
    protected $_menu_path = 'inventoryplus/stock_control/transfer_stock/sendstock';
    
    
    public function indexAction() {
        $this->_title($this->__('Inventory'))
                ->_title($this->__('Manage Send Stock'));
        $this->loadLayout()->_setActiveMenu($this->_menu_path);
        $this->renderLayout();
    }

    public function newAction() {
        $this->_title($this->__('Inventory'))
                ->_title($this->__('Send Stock'));
        $this->loadLayout()->_setActiveMenu($this->_menu_path);

        $this->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Manage Stock Sending'), Mage::helper('adminhtml')->__('Manage Stock Sending')
        );
        $this->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Stock Sending News'), Mage::helper('adminhtml')->__('Stock Sending News')
        );
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        $this->_addContent($this->getLayout()->createBlock('inventorywarehouse/adminhtml_sendstock_edit'))
                ->_addLeft($this->getLayout()->createBlock('inventorywarehouse/adminhtml_sendstock_edit_tabs'));
        $this->renderLayout();
    }

    public function editAction() {
        $this->_title($this->__('Inventory'))
                ->_title($this->__('Send Stock'));
        $sendstock = $this->getRequest()->getParam('id');
        $model = Mage::getModel('inventorywarehouse/sendstock')->load($sendstock);

        if ($model->getId() || $sendstock == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);

            if (!empty($data)) {
                $model->setData($data);
            }
            Mage::register('sendstock_data', $model);

            $this->loadLayout()->_setActiveMenu($this->_menu_path);
            $this->_addBreadcrumb(
                    Mage::helper('adminhtml')->__('Manage Stock Sending'), Mage::helper('adminhtml')->__('Manage Stock Sending')
            );
            $this->_addBreadcrumb(
                    Mage::helper('adminhtml')->__('Stock Sending News'), Mage::helper('adminhtml')->__('Stock Sending News')
            );

            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock('inventorywarehouse/adminhtml_sendstock_edit'))
                    ->_addLeft($this->getLayout()->createBlock('inventorywarehouse/adminhtml_sendstock_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('inventoryplus')->__('Item does not exist')
            );
            $this->_redirect('*/*/');
        }
    }

    public function productsAction() {
        $this->loadLayout();

        $this->getLayout()->getBlock('sendstock.edit.tab.products')
                ->setProducts($this->getRequest()->getPost('sendstock_products', null));
        $this->renderLayout();
        if (Mage::getModel('admin/session')->getData('sendstock_product_import'))
            Mage::getModel('admin/session')->setData('sendstock_product_import', null);
    }

    public function productsGridAction() {
        $this->loadLayout();
        $this->getLayout()->getBlock('sendstock.edit.tab.products')
                ->setProducts($this->getRequest()->getPost('sendstock_products', null));
        $this->renderLayout();
    }

    public function gridAction() {
        $this->loadLayout();
        $this->renderLayout();
    }
    public function createalldeliveryAction(){
        if ($sendstock_id = Mage::app()->getRequest()->getParam('sendstock_id')) {
            $sendstock_id = Mage::app()->getRequest()->getParam('sendstock_id');
            $sendstock = Mage::getModel('inventorywarehouse/sendstock')->load($sendstock_id);
            if ($sendstock->getData('warehouse_id_from') != 0)
                $warehourseSource = Mage::getModel('inventoryplus/warehouse')->load($sendstock->getData('warehouse_id_from'));
            $warehourseTarget = Mage::getModel('inventoryplus/warehouse')->load($sendstock->getData('warehouse_id_to'));

            $admin = Mage::getModel('admin/session')->getUser()->getUsername();
            $now = now();

            //create send transaction data
            $transactionSendModel = Mage::getModel('inventorywarehouse/transaction');
            $transactionSendData = array();
            if ($sendstock->getData('warehouse_id_from') != 0) {
                $transactionSendData['type'] = '1';
            } else {
                $transactionSendData['type'] = '7';
            }

            $transactionSendData['warehouse_id_from'] = $transactionSendData['warehouse_name_from'] = $transactionSendData['warehouse_id_to'] = $transactionSendData['warehouse_name_to'] = '';
            $transactionSendData['warehouse_id_from'] = $sendstock->getData('warehouse_id_from');
            $transactionSendData['warehouse_name_from'] = $sendstock->getData('warehouse_name_from');
            $transactionSendData['warehouse_id_to'] = $sendstock->getData('warehouse_id_to');
            $transactionSendData['warehouse_name_to'] = $sendstock->getData('warehouse_name_to');
            $transactionSendData['created_at'] = $now;
            $transactionSendData['created_by'] = $admin;
            $transactionSendData['reason'] = $sendstock->getData('reason');
            $transactionSendModel->addData($transactionSendData);

            //create receive transaction data
            $transactionReceiveData['warehouse_id_from'] = $transactionReceiveData['warehouse_name_from'] = $transactionReceiveData['warehouse_id_to'] = $transactionReceiveData['warehouse_name_to'] = '';
            $transactionReceiveModel = Mage::getModel('inventorywarehouse/transaction');
            $transactionReceiveData = array();
            if ($sendstock->getData('warehouse_id_from') != 0) {
                $transactionReceiveData['type'] = '2';
            } else {
                $transactionReceiveData['type'] = '8';
            }
//
            $transactionReceiveData['warehouse_id_from'] = $sendstock->getData('warehouse_id_from');
            $transactionReceiveData['warehouse_name_from'] = $sendstock->getData('warehouse_name_from');
            $transactionReceiveData['warehouse_id_to'] = $sendstock->getData('warehouse_id_to');
            $transactionReceiveData['warehouse_name_to'] = $sendstock->getData('warehouse_name_to');
            $transactionReceiveData['created_at'] = $now;
            $transactionReceiveData['created_by'] = $admin;
            $transactionReceiveData['reason'] = $sendstock->getData('reason');
            $transactionReceiveModel->addData($transactionReceiveData);
            $transactionSendModel->save();
            $transactionReceiveModel->save();
            //save product
            $sendstockProducts = Mage::getModel('inventorywarehouse/sendstock_product')->getCollection()
                ->addFieldToFilter('warehouse_sendstock_id', $sendstock_id);
            $total = array();
            $notReceive = array();
            $source = $target = '';
            $source = $sendstock->getData('warehouse_id_from');
            $target = $sendstock->getData('warehouse_id_to');
            Zend_Debug::dump($sendstock->getData());
            foreach ($sendstockProducts as $sendstockProduct){
                $qty_send = abs($sendstockProduct->getQty());
                $qty_delivery = abs($sendstockProduct->getTotalDelivery());
                if($qty_delivery == $qty_send)
                    continue;
                $pId = $sendstockProduct->getProductId();
                $product = Mage::getModel('catalog/product')->load($pId);

                /*save in rsendstock product*/
                $sendstockProductsItem = Mage::getModel('inventorywarehouse/sendstock_product')
                    ->getCollection()
                    ->addFieldToFilter('warehouse_sendstock_id', $sendstock->getId())
                    ->addFieldToFilter('product_id', $pId)
                    ->setPageSize(1)
                    ->setCurPage(1)
                    ->getFirstItem();
                $qty_delivery = $qty_send - $qty_delivery;
                $qty_send = abs($sendstockProductsItem->getQty());
                $total_delivery = abs($sendstockProductsItem->getTotalDelivery());
                if ($qty_send >= ($total_delivery + $qty_delivery)) {
                    $qty = $qty_delivery;
                } else {
                    $qty = $qty_send - $total_delivery;
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
                $sendstockDelivery = Mage::getModel('inventorywarehouse/sendstockdelivery');
                $sendstockDelivery->setWarehouseSendstockId($sendstock_id)
                    ->setTime($now)
                    ->setProductId($pId)
                    ->setProductName($product->getName())
                    ->setProductSku($product->getSku())
                    ->setQtyDelivery($qty)
                    ->setCreatedBy($admin)
                    ->save();
                $sendstockProductsItem->setTotalDelivery($total_delivery + $qty)->save();
                array_push($total, (int)$qty);

                $warehouseProductTarget = Mage::getModel('inventoryplus/warehouse_product')
                    ->getCollection()
                    ->addFieldToFilter('warehouse_id', $target)
                    ->addFieldToFilter('product_id', $pId)
                    ->setPageSize(1)
                    ->setCurPage(1)
                    ->getFirstItem();
                Zend_Debug::dump($warehouseProductTarget->getData());
//                die();
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


            $sendstockProducts = Mage::getModel('inventorywarehouse/sendstock_product')->getCollection()
                ->addFieldToFilter('warehouse_sendstock_id', $sendstock_id);
            $complete = true;
            $processing = false;
            foreach ($sendstockProducts as $sendstockProduct){
                if(abs($sendstockProduct->getQty()) != abs($sendstockProduct->getTotalDelivery()))
                    $complete = false;
                if($sendstockProduct->getTotalDelivery() != 0)
                    $processing = true;
            }

            if($processing)
                $sendstock->setStatus(4)->save();
            if ($complete)
                $sendstock->setStatus(1)->save();
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('inventorywarehouse')->__('Delivery stock request was successfully created.')
            );

            $this->_redirect('adminhtml/inw_sendstock/edit', array('id' => $sendstock_id));
            return;

        }else{
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('inventorywarehouse')->__('Unable to save')
            );
            $this->_redirect('*/*/');
        }
    }
    public function saveAction() {
        $data = $this->getRequest()->getPost();
        if ($data) {
            //save send stock information
            $model = Mage::getModel('inventorywarehouse/sendstock')->load($this->getRequest()->getParam('id'));
            if (isset($data['warehouse_source'])) {
                $data['warehouse_id_from'] = $data['warehouse_source'];
            }
            if (isset($data['warehouse_target'])) {
                $data['warehouse_id_to'] = $data['warehouse_target'];
            }
            $warehourseSource = Mage::getModel('inventoryplus/warehouse')->load($data['warehouse_id_from']);
            if ($data['warehouse_id_to'] != 'others') {
                $warehourseTarget = Mage::getModel('inventoryplus/warehouse')->load($data['warehouse_id_to']);
                if ($warehourseTarget->getWarehouseName())
                    $data['warehouse_name_to'] = $warehourseTarget->getWarehouseName();
            }else if ($data['warehouse_id_to'] == 'others') {
                $data['warehouse_id_to'] = '';
                $data['warehouse_name_to'] = 'Others';
            }
            if ($warehourseSource->getWarehouseName())
                $data['warehouse_name_from'] = $warehourseSource->getWarehouseName();
            $createdAt = date('Y-m-d', strtotime(now()));
            $data['created_at'] = $createdAt;
            $admin = Mage::getModel('admin/session')->getUser()->getUsername();
            if ($this->getRequest()->getParam('id')) {
                $data['created_by'] = $model->getData('created_by');
            } else {
                $data['created_by'] = $admin;
            }
            $data['status'] = 3;
            $model->addData($data);

            //create send transaction data
            $transactionSendModel = Mage::getModel('inventorywarehouse/transaction');
            $transactionSendData = array();
            $transactionSendData['type'] = '1';
            $transactionSendData['warehouse_id_from'] = $data['warehouse_id_from'];
            $transactionSendData['warehouse_name_from'] = $data['warehouse_name_from'];
            $transactionSendData['warehouse_id_to'] = $data['warehouse_id_to'];
            $transactionSendData['warehouse_name_to'] = $data['warehouse_name_to'];
            $transactionSendData['created_at'] = $data['created_at'];
            $transactionSendData['created_by'] = $data['created_by'];
            $transactionSendData['reason'] = $data['reason'];
            $transactionSendModel->addData($transactionSendData);

            //create receive transaction data
            $transactionReceiveModel = Mage::getModel('inventorywarehouse/transaction');
            if ($data['warehouse_id_to'] != '') {
                $transactionReceiveData = array();
                $transactionReceiveData['type'] = '2';
                $transactionReceiveData['warehouse_id_from'] = $data['warehouse_id_from'];
                $transactionReceiveData['warehouse_name_from'] = $data['warehouse_name_from'];
                $transactionReceiveData['warehouse_id_to'] = $data['warehouse_id_to'];
                $transactionReceiveData['warehouse_name_to'] = $data['warehouse_name_to'];
                $transactionReceiveData['created_at'] = $data['created_at'];
                $transactionReceiveData['created_by'] = $data['created_by'];
                $transactionReceiveData['reason'] = $data['reason'];
                $transactionReceiveModel->addData($transactionReceiveData);
            }

            try {
                //save data
                $model->save();
                $transactionSendModel->save();
                $transactionReceiveModel->save();
                //save products
                if (isset($data['sendstock_products'])) {
                    $sendstockProducts = array();
                    $total = array();
                    Mage::helper('inventoryplus')->parseStr(urldecode($data['sendstock_products']), $sendstockProducts);
                    if (count($sendstockProducts)) {
                        foreach ($sendstockProducts as $pId => $enCoded) {
                            $product = Mage::getModel('catalog/product')->load($pId);
                            $codeArr = array();
                            $qty = 0;
                            Mage::helper('inventoryplus')->parseStr(Mage::helper('inventoryplus')->base64Decode($enCoded), $codeArr);
                            $send_warehouse_products = Mage::getModel('inventoryplus/warehouse_product')
                                    ->getCollection()
                                    ->addFieldToFilter('warehouse_id', $data['warehouse_id_from'])
                                    ->addFieldToFilter('product_id', $pId)
                                    ->setPageSize(1)
                                    ->setCurPage(1)
                                    ->getFirstItem();
                            /** if source warhouse has not this product */
                            if (!$send_warehouse_products->getId())
                                continue;
                            if (!empty($codeArr['qty'])) {
                                if ((int) $codeArr['qty'] > (int) $send_warehouse_products->getTotalQty()) {
                                    $qty = $send_warehouse_products->getTotalQty();
                                } else {
                                    $qty = $codeArr['qty'];
                                }
                                $total[] = $qty;
                            }
                            //save products to sendstock product table
                            Mage::getModel('inventorywarehouse/sendstock_product')
                                    ->setWarehouseSendstockId($model->getId())
                                    ->setProductId($pId)
                                    ->setProductSku($product->getSku())
                                    ->setProductName($product->getName())
                                    ->setQty((-$qty))
                                    ->save()
                            ;
                            //save products to transaction product table for send transaction

                            Mage::getModel('inventorywarehouse/transaction_product')
                                    ->setWarehouseTransactionId($transactionSendModel->getId())
                                    ->setProductId($pId)
                                    ->setProductSku($product->getSku())
                                    ->setProductName($product->getName())
                                    ->setQty(-$qty)
                                    ->save();
                            //save products to transaction product table for receive transaction
                            if ($transactionReceiveModel->getId()) {
                                Mage::getModel('inventorywarehouse/transaction_product')
                                        ->setWarehouseTransactionId($transactionReceiveModel->getId())
                                        ->setProductId($pId)
                                        ->setProductSku($product->getSku())
                                        ->setProductName($product->getName())
                                        ->setQty($qty)
                                        ->save()
                                ;
                            }
                            //Recalculate products for sending warehouse
                            $new_send_warehouse_qty = $send_warehouse_products->getTotalQty() - $qty;
                            $new_send_warehouse_qty_available = $send_warehouse_products->getAvailableQty() - $qty;
                            $send_warehouse_products->setTotalQty($new_send_warehouse_qty)
                                    ->setAvailableQty($new_send_warehouse_qty_available);
//                                    ->save();
                            //Recalculate products for receiving warehouse
                            if ($data['warehouse_id_to'] != '') {
                                $receive_warehouse_products = Mage::getModel('inventoryplus/warehouse_product')
                                        ->getCollection()
                                        ->addFieldToFilter('warehouse_id', $data['warehouse_id_to'])
                                        ->addFieldToFilter('product_id', $pId)
                                        ->setPageSize(1)
                                        ->setCurPage(1)
                                        ->getFirstItem();
                                if ($receive_warehouse_products->getId()) {
                                    $new_receive_warehouse_qty = $receive_warehouse_products->getTotalQty() + $qty;
                                    $new_receive_warehouse_qty_available = $receive_warehouse_products->getAvailableQty() + $qty;
                                    $receive_warehouse_products
                                            ->setTotalQty($new_receive_warehouse_qty)
                                            ->setAvailableQty($new_receive_warehouse_qty_available);
//                                            ->save();
                                } else {
                                    Mage::getModel('inventoryplus/warehouse_product')
                                            ->setWarehouseId($data['warehouse_id_to'])
                                            ->setProductId($pId)
                                            ->setTotalQty($qty)
                                            ->setAvailableQty($qty);
//                                            ->save();
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
//                                $stock_item->setQty($new_stock_qty)->save();
                                $stock_item->setQty($new_stock_qty);
                            }
                        }
                    }
                }
                //save total products for sendstock                
                $totalProducts = array_sum($total);
                $model->setTotalProducts(-$totalProducts);
                $model->save();

                //save total products and send_stock id for transaction                  
                $transactionSendModel
                        ->setTotalProducts(-$totalProducts)
                        ->setWarehouseSendstockId($model->getId());
                $transactionSendModel->save();



                if ($data['warehouse_id_to'] != '') {
                    $transactionReceiveModel
                            ->setWarehouseSendstockId($model->getId())
                            ->setTotalProducts($totalProducts);
                    $transactionReceiveModel->save();
                }

                //send email to admin of receive warehouse
                if (Mage::getStoreConfig('inventoryplus/transaction/transaction_notice') == 1) {
                    $stockName = "Send stock No." . $model->getId();
                    if ($data['warehouse_id_to'] != '' || $data['warehouse_id_to'] != '1') {
                        $warehouseTarget = Mage::getModel('inventoryplus/warehouse')->load($data['warehouse_id_to']);
                        try {
                            if ($warehouseTarget) {
                                Mage::helper('inventorywarehouse/email')->sendSendstockEmail($warehouseTarget, $model->getId(), 1, $stockName);
                            }
                        } catch (Exception $e) {
                            Mage::log($e->getMessage(), null, 'inventory_management.log');
                        }
                    }
                }
                /* Remove scan data if using barcode scanner */
                $this->removeScanData();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                        Mage::helper('inventoryplus')->__('Stock sending was successfully created.')
                );
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('adminhtml/inw_sendstock/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('adminhtml/inw_sendstock/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }

            Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('inventoryplus')->__('Unable to save')
            );
            $this->_redirect('*/*/');
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('inventoryplus')->__('Unable to save')
            );
            $this->_redirect('*/*/');
        }
    }

    public function checkproductAction() {
        $sendstock_products = $this->getRequest()->getPost('products');
        $checkProduct = 0;
        $next = false;
        if (isset($sendstock_products)) {
            $sendstockProducts = array();
            $sendstockProductsExplodes = explode('&', urldecode($sendstock_products));
            if (count($sendstockProductsExplodes) <= 900) {
                Mage::helper('inventoryplus')->parseStr(urldecode($sendstock_products), $sendstockProducts);
            } else {
                foreach ($sendstockProductsExplodes as $sendstockProductsExplode) {
                    $sendstockProduct = '';
                    Mage::helper('inventoryplus')->parseStr($sendstockProductsExplode, $sendstockProduct);
                    $sendstockProducts = $sendstockProducts + $sendstockProduct;
                }
            }
            if (count($sendstockProducts)) {
                foreach ($sendstockProducts as $pId => $enCoded) {
                    $codeArr = array();
                    Mage::helper('inventoryplus')->parseStr(Mage::helper('inventoryplus')->base64Decode($enCoded), $codeArr);
                    if (is_numeric($codeArr['qty']) && $codeArr['qty'] > 0) {
                        $checkProduct = 1;
                        $next = true;
                        break;
                    }
                }
            }
        }
        echo $checkProduct;
    }

    public function cancelAction() {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('inventorywarehouse/sendstock')->load($id);
        $send_warehouse = $model->getWarehouseIdTo();
        $receive_warehouse = $model->getWarehouseIdFrom();
        try {
            //change status of send stock record
            $model->setStatus(2)->save();
            //create send transaction
            $transactionSendModel = Mage::getModel('inventorywarehouse/transaction');
            $transactionSendData = array();
            $transactionSendData['type'] = '1';
            $transactionSendData['warehouse_id_from'] = $model->getWarehouseIdTo();
            $transactionSendData['warehouse_name_from'] = $model->getWarehouseNameTo();
            $transactionSendData['warehouse_id_to'] = $model->getWarehouseIdFrom();
            $transactionSendData['warehouse_name_to'] = $model->getWarehouseNameFrom();
            $transactionSendData['created_at'] = $model->getCreatedAt();
            $transactionSendData['created_by'] = $model->getCreatedBy();
            $transactionSendData['reason'] = Mage::helper('inventorywarehouse')->__("Cancel Stock Sending No.'%s'", $id);
            $transactionSendData['total_products'] = $model->getTotalProducts();
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
            $transactionReceiveData['reason'] = Mage::helper('inventorywarehouse')->__("Cancel Stock Sending No.'%s'", $id);
            $transactionReceiveData['total_products'] = -$model->getTotalProducts();
            $transactionReceiveModel->addData($transactionReceiveData);
            $transactionReceiveModel->save();

            //recalculate qty
            $sendstockProducts = Mage::getModel('inventorywarehouse/sendstock_product')
                    ->getCollection()
                    ->addFieldToFilter('warehouse_sendstock_id', $id);
            foreach ($sendstockProducts as $sendstockproduct) {
                $pId = $sendstockproduct->getProductId();
                $pSku = $sendstockproduct->getProductSku();
                $pName = $sendstockproduct->getProductName();
                //get qty of product using for transaction
                //qty is negative
                $qty = $sendstockproduct->getQty();
                //save products to transaction product table for send transaction
                Mage::getModel('inventorywarehouse/transaction_product')
                        ->setWarehouseTransactionId($transactionSendModel->getId())
                        ->setProductId($pId)
                        ->setProductSku($pSku)
                        ->setProductName($pName)
                        ->setQty($qty)
                        ->save()
                ;
                //save products to transaction product table for receive transaction
                Mage::getModel('inventorywarehouse/transaction_product')
                        ->setWarehouseTransactionId($transactionReceiveModel->getId())
                        ->setProductId($pId)
                        ->setProductSku($pSku)
                        ->setProductName($pName)
                        ->setQty(-$qty)
                        ->save()
                ;
                //recalculate product qty for warehouse send
                if ($send_warehouse != 0) {
                    $send_warehouse_products = Mage::getModel('inventoryplus/warehouse_product')
                            ->getCollection()
                            ->addFieldToFilter('warehouse_id', $send_warehouse)
                            ->addFieldToFilter('product_id', $pId)
                            ->setPageSize(1)
                            ->setCurPage(1)
                            ->getFirstItem();
                    $newProductsQtySend = $send_warehouse_products->getTotalQty() + $qty;
                    $newProductsQtyAvaSend = $send_warehouse_products->getAvailableQty() + $qty;
                    if ($newProductsQtyAvaSend == 0 && $newProductsQtySend == 0) {
                        $send_warehouse_products->delete();
                    } else {
                        $send_warehouse_products
                                ->setTotalQty($newProductsQtySend)
                                ->setAvailableQty($newProductsQtyAvaSend)
                                ->save();
                    }
                } else {
                    //recalculate product qty for system if other
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
                //recalculate product qty for warehouses receive
                $receive_warehouse_products = Mage::getModel('inventoryplus/warehouse_product')
                        ->getCollection()
                        ->addFieldToFilter('warehouse_id', $receive_warehouse)
                        ->addFieldToFilter('product_id', $pId)
                        ->setPageSize(1)
                        ->setCurPage(1)
                        ->getFirstItem();
                if (!$receive_warehouse_products->getId()) {
                    $receive_warehouse_products = Mage::getModel('inventoryplus/warehouse_product')
                            ->setData('product_id', $pId)
                            ->setData('warehouse_id', $receive_warehouse)
                            ->setData('total_qty', - $qty)
                            ->setData('available_qty', - $qty)
                            ->save();
                } else {
                    $newProductsQtyReceive = $receive_warehouse_products->getTotalQty() - $qty;
                    $newProductsQtyAvaReceive = $receive_warehouse_products->getAvailableQty() - $qty;
                    $receive_warehouse_products
                            ->setTotalQty($newProductsQtyReceive)
                            ->setAvailableQty($newProductsQtyAvaReceive)
                            ->save();
                }
            }

            //send email to admin of receive warehouse
            if (Mage::getStoreConfig('inventoryplus/transaction/transaction_notice') == 1) {
                if ($receive_warehouse) {
                    $warehouseTarget = Mage::getModel('inventoryplus/warehouse')->load($receive_warehouse);
                    if ($warehouseTarget && !$warehouseTarget->getIsUnwarehouse()) {
                        $stockName = "Cancel send stock No." . $model->getId();
                        Mage::helper('inventorywarehouse/email')->sendSendstockEmail($warehouseTarget, $model->getId(), 1, $stockName);
                    }
                }
            }

            Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('inventorywarehouse')->__('Stock Sending was successfully canceled.')
            );
            if ($this->getRequest()->getParam('warehouse_id')) {
                $this->_redirect('adminhtml/inw_warehouse/edit', array('id' => $this->getRequest()->getParam('warehouse_id')));
            } else {
                $this->_redirect('adminhtml/inw_sendstock/index');
            }
            return;
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_redirect('adminhtml/inw_sendstock/edit', array('id' => $this->getRequest()->getParam('id')));
            return;
        }

        Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('inventoryplus')->__('Unable to cancel')
        );
        if ($this->getRequest()->getParam('warehouse_id')) {
            $this->_redirect('adminhtml/inw_warehouse/edit', array('id' => $this->getRequest()->getParam('warehouse_id')));
        } else {
            $this->_redirect('adminhtml/inw_sendstock/index');
        }
    }

    public function getImportCsvAction() {
        $fileToUpload = Mage::helper('inventoryplus')->getUploadFile();
        if (isset($fileToUpload['fileToUpload']['name']) && $fileToUpload['fileToUpload']['name'] != '') {
            try {
                Mage::getModel('admin/session')->setData('send_stock_reason', null);
                if ($this->getRequest()->getParam('reason')) {
                    Mage::getModel('admin/session')->setData('send_stock_reason', $this->getRequest()->getParam('reason'));
                }
                $fileName = $fileToUpload['fileToUpload']['tmp_name'];
                $Object = new Varien_File_Csv();
                $dataFile = $Object->getData($fileName);
                $sendstockProduct = array();
                $sendstockProducts = array();
                $fields = array();
                $helper = Mage::helper('inventorywarehouse/sendstock');
                if (count($dataFile)) {
                    foreach ($dataFile as $col => $row) {
                        if ($col == 0) {
                            if (!empty($row))
                                foreach ($row as $index => $cell)
                                    $fields[$index] = (string) $cell;
                        }elseif ($col > 0) {
                            if (!empty($row))
                                foreach ($row as $index => $cell) {

                                    if (isset($fields[$index])) {
                                        $sendstockProduct[$fields[$index]] = $cell;
                                    }
                                }
                            $source = $this->getRequest()->getParam('source');
                            $product = Mage::getResourceModel('catalog/product_collection')
                                                ->addAttributeToFilter('sku', $sendstockProduct['SKU'])
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
                                $sendstockProducts[] = $sendstockProduct;
                            }
                        }
                    }
                }
                $helper->importProduct($sendstockProducts);
            } catch (Exception $e) {
                Mage::log($e->getMessage(),null,'inventory_warehouse.log');
            }
        }
    }

    public function exportCsvAction() {
        $fileName = 'send_stock.csv';
        $content = $this->getLayout()
                ->createBlock('inventorywarehouse/adminhtml_sendstock_grid')
                ->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * export grid item to XML type
     */
    public function exportXmlAction() {
        $fileName = 'send_stock.xml';
        $content = $this->getLayout()
                ->createBlock('inventorywarehouse/adminhtml_sendstock_grid')
                ->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function exportpdfsendstockAction() {
        try {
            $warehouse_sendstock_id = $this->getRequest()->getParam('id');
            $sendstockProducts = Mage::getModel('inventorywarehouse/sendstock_product')->getCollection()
                    ->addFieldToFilter('warehouse_sendstock_id', $warehouse_sendstock_id)
                    ->getData();
            $img = Mage::getDesign()->getSkinUrl('images/logo_email.gif', array('_area' => 'frontend'));
            $contents = '<div><img src="' . $img . '" /></div>';
            $contents .= $this->getLayout()->createBlock('inventorywarehouse/adminhtml_sendstock')
                    ->setSendstockid($warehouse_sendstock_id)
                    ->setSendstockproducts($sendstockProducts)
                    ->setTemplate('inventorywarehouse/sendstock/sendstock.phtml')
                    ->toHtml();
            include("lib/MPDF56/mpdf.php");

            $mpdf = new mPDF();

            $mpdf->WriteHTML($contents);
            $fileName = 'send-stock-list-'. Mage::helper('core')->formatDate(now(),'short') . '-' . $warehouse_sendstock_id;
            $mpdf->Output($fileName . '.pdf', 'D');
            exit;
        } catch (HTML2PDF_exception $e) {
            Mage::log($e->getMessage(), null, 'inventory_warehouse.log');
            exit;
        }
    }

    public function removeScanData() {
		if(!Mage::helper('core')->isModuleEnabled('Magestore_Inventorybarcode')) {
            return;
        }
        $action = Magestore_Inventorywarehouse_Block_Adminhtml_Sendstock_Scanbarcode::SCAN_ACTION;
        Mage::getModel('inventorybarcode/barcode_scanitem')->reset($action);
    }

}
