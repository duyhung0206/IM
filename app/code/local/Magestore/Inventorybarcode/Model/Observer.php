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
 * @package     Magestore_Inventorybarcode
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Inventorybarcode Observer Model
 * 
 * @category    Magestore
 * @package     Magestore_Inventorybarcode
 * @author      Magestore Developer
 */
class Magestore_Inventorybarcode_Model_Observer {

    public function saveDeliveryBefore($observer) {
        $data = $observer->getEvent()->getProducts();
        $purchaseOrderId = $observer->getEvent()->getPurchaseOrderId();
        //check dupplicate
        $warehouseIds = '';
        $barcode = array();
        $i = 0;

        foreach ($data as $productId => $enCoded) {
            $codeArr = array();
            Mage::helper('inventoryplus')->parseStr(Mage::helper('inventoryplus')->base64Decode($enCoded), $codeArr);
            /*Michael 201602 */
            if (!isset($codeArr['barcode']) || !$codeArr['barcode'])
                continue;
            foreach ($codeArr as $warehouse => $value) {
                if ($i > 0)
                    break;
                $id = explode('_', $warehouse);
                if ($id[0] == 'warehouse') {
                    if (!$warehouseIds) {
                        $warehouseIds = $id[1];
                    } else {
                        $warehouseIds .= ',' . $id[1];
                    }
                }
                $i++;
            }
//check dupplicate
            /*Michael 201602 */

            if (in_array($codeArr['barcode'], $barcode)) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('inventoryplus')->__('The barcode "%s" was already duplicate!', $codeArr['barcode'])
                );
                $url = Mage::helper("adminhtml")->getUrl("adminhtml/inpu_purchaseorders/newdelivery/", array('purchaseorder_id' => $purchaseOrderId, 'warehouse_ids' => $warehouseIds, 'action' => 'newdelivery',
                    'active' => 'delivery'));
//                header('Location:' . $url);
//                exit;
                Mage::app()->getResponse()
                    ->setRedirect($url)->sendResponse();
            } else {
                $barcode[] = $codeArr['barcode'];
            }
            //check exist
            if(Mage::getStoreConfig('inventoryplus/barcode/use_multiple_barcode')) {
                $checkBarcodeExist = Mage::getModel('inventorybarcode/barcode')->load($codeArr['barcode'], 'barcode');
                if ($checkBarcodeExist->getId()) {
                    Mage::getSingleton('adminhtml/session')->addError(
                        Mage::helper('inventorybarcode')->__('The barcode "%s" was already exist!', $codeArr['barcode'])
                    );
                    $url = Mage::helper("adminhtml")->getUrl("adminhtml/inpu_purchaseorders/newdelivery/", array('purchaseorder_id' => $purchaseOrderId, 'warehouse_ids' => $warehouseIds, 'action' => 'newdelivery',
                        'active' => 'delivery'));
//                    header('Location:' . $url);
//                    exit;
                    Mage::app()->getResponse()
                        ->setRedirect($url)->sendResponse();
                }
            }else{
                $barcodeAtt = Mage::getStoreConfig('inventoryplus/barcode/barcode_attribute');
                //check exist
//                $checkBarcodeExist = Mage::getModel('catalog/product')->load($codeArr[$barcodeAtt], $barcodeAtt);
                $checkBarcodeExist = Mage::getModel('catalog/product')->getCollection()
                                            ->addAttributeToFilter($barcodeAtt, $codeArr['barcode'])
                                            ->setPageSize(1)->setCurPage(1)->getFirstItem();
                if ($checkBarcodeExist->getId()) {
                    Mage::getSingleton('adminhtml/session')->addError(
                        Mage::helper('inventorybarcode')->__('The barcode "%s" was already exist!', $codeArr['barcode'])
                    );
                    $url = Mage::helper("adminhtml")->getUrl("adminhtml/inpu_purchaseorders/newdelivery/", array('purchaseorder_id' => $purchaseOrderId, 'warehouse_ids' => $warehouseIds, 'action' => 'newdelivery',
                        'active' => 'delivery'));
//                    header('Location:' . $url);
//                    exit;
                    Mage::app()->getResponse()
                        ->setRedirect($url)->sendResponse();
                }
            }
        }
    }

    public function purchaseorderDeliverySaveAfter($observer) {
        if (Mage::helper('inventorybarcode')->isMultipleBarcode() && !Mage::getStoreConfig('inventoryplus/barcode/createbarcode_afterdelivery'))
            return;
        $sqlNews = array();
        $codeArr = array();

        $purchaseOrderId = $observer->getEvent()->getPurchaseOrderId();
        $purchaseOrder = Mage::getModel('inventorypurchasing/purchaseorder')->load($purchaseOrderId);


        $suppliererId = $purchaseOrder->getSupplierId();
        $productId = $observer->getEvent()->getProductId();
        $purchaseOrderProduct = Mage::getModel('inventorypurchasing/purchaseorder_product')->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('purchase_order_id', $purchaseOrderId)
                ->setPageSize(1)->setCurPage(1)->getFirstItem();

        $deliverys = $observer->getEvent()->getData('data');

        /*Michael 201602 - update attribute to product that is using be barcode - single barcode*/
        //auto generate barcode
        if(!Mage::helper('inventorybarcode')->isMultipleBarcode()) {
            $barcodeAtt = Mage::getStoreConfig('inventoryplus/barcode/barcode_attribute');
            $product = Mage::getModel('catalog/product')->load($productId);
            if($product->getData($barcodeAtt)){
                $codeArr['barcode'] = $product->getData($barcodeAtt);
            }else {
                if (!isset($deliverys['barcode']) || $deliverys['barcode'] == '') {
                    $codeArr['barcode'] = Mage::helper('inventorybarcode')->generateCode(Mage::getStoreConfig('inventoryplus/barcode/pattern'));
                    $checkBarcodeExist = Mage::getModel('catalog/product')->getCollection()
                        ->addAttributeToFilter($barcodeAtt, $codeArr['barcode'])
                        ->setPageSize(1)->setCurPage(1)->getFirstItem();
                    while ($checkBarcodeExist->getId()) {
                        $codeArr['barcode'] = Mage::helper('inventorybarcode')->generateCode(Mage::getStoreConfig('inventoryplus/barcode/pattern'));
                        $checkBarcodeExist = Mage::getModel('catalog/product')->getCollection()
                            ->addAttributeToFilter($barcodeAtt, $codeArr['barcode'])
                            ->setPageSize(1)->setCurPage(1)->getFirstItem();
                    }
                } else {
                    $codeArr['barcode'] = $deliverys['barcode'];
                }
                $product->setData($barcodeAtt, $codeArr['barcode']);
                try {
                    $product->save();
                } catch (Exception $e) {
                    Mage::log($e->getMessage(), null, 'inventory_management.log');
                }
            }
            $deliverys['barcode'] = $codeArr['barcode'];
        }
        $warehouseIds = array();
        foreach ($deliverys as $warehouse => $value) {
            if ($value) {
                $id = explode('_', $warehouse);
                if ($id[0] == 'warehouse') {
                    $warehouseIds[] = $id[1];
                }
            }
        }


        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');

        $tablename = 'inventorybarcode/barcode';

        $results = Mage::helper('inventorybarcode/attribute')->getAllColumOfTable($tablename);

        $columns = array();
        $string = '';
        $type = '';

        foreach ($results as $result) {
            $fields = explode('_', $result);
            if ($fields[0] == 'barcode' || $fields[0] == 'qty')
                continue;
            foreach ($fields as $id => $field) {
                if ($id == 0)
                    $type = $field;
                if ($id == 1) {
                    $string = $field;
                }
                if ($id > 1)
                    $string = $string . '_' . $field;
            }
            $columns[] = array($type => $string);
            $string = '';
            $type = '';
        }

        $codeArr['purchaseorder_purchase_order_id'] = $purchaseOrderId;
        $codeArr['supplier_supplier_id'] = $suppliererId;
        $codeArr['barcode_status'] = 1;
        $codeArr['warehouse_warehouse_id'] = $warehouseIds;

        //auto generate barcode
        if (!isset($deliverys['barcode']) || $deliverys['barcode'] == '') {
            $codeArr['barcode'] = Mage::helper('inventorybarcode')->generateCode(Mage::getStoreConfig('inventoryplus/barcode/pattern'));
        } else {
            $codeArr['barcode'] = $deliverys['barcode'];
        }
        $delivery = $observer->getEvent()->getDelivery();
        $delivery->setBarcode($codeArr['barcode']);
        $delivery->save();
        $sqlNews['barcode'] = $codeArr['barcode'];
        $sqlNews['barcode_status'] = $codeArr['barcode_status'];
        $sqlNews['qty'] = $deliverys['qty_delivery'];


        foreach ($columns as $id => $column) {
            $i = 0;
            $columnName = '';

            foreach ($column as $id => $key) {
                if ($i == 0)
                    $columnName = $id . '_' . $key;
                if ($i > 0)
                    $columnName = $columnName . '_' . $key;

                $i++;
            }

            if ($id != 'custom') {
                $return = Mage::helper('inventorybarcode')->getValueForBarcode($id, $key, $productId, $codeArr);
                if (is_array($return)) {
                    foreach ($return as $columns) {
                        foreach ($columns as $column => $value) {
                            if (!isset($sqlNews[$id . '_' . $column])) {
                                $sqlNews[$id . '_' . $column] = $value;
                            } else {
                                $sqlNews[$id . '_' . $column] .= ',' . $value;
                            }
                        }
                    }
                } else {
                    $sqlNews[$columnName] = $return;
                }
            }
        }
        $sqlNews['created_date'] = now();
        $sqlNews['qty_original'] = $deliverys['qty_delivery'];

        $admin = Mage::getModel('admin/session')->getUser()->getUsername();
        //create action log
        Mage::getModel('inventorybarcode/barcode_actionlog')->setData('barcode_action', Mage::helper('inventorybarcode')->__('Barcode "%s" was created automatically for Purchase order id #%s', $codeArr['barcode'], $codeArr['purchaseorder_purchase_order_id']))
                ->setData('created_at', now())
                ->setData('created_by', $admin)
                ->setData('barcode', $codeArr['barcode'])
                ->save();

        if(Mage::helper('inventorybarcode')->isMultipleBarcode())
            $writeConnection->insertMultiple($resource->getTableName('inventorybarcode/barcode'), $sqlNews);
        $sqlNews = array();



        try {
            $purchaseOrderProduct->setData('barcode', $codeArr['barcode'])
                    ->save();
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'inventory_management.log');
        }
    }

    public function saveAllDeliveryAfter($observer) {
        if (Mage::helper('inventorybarcode')->isMultipleBarcode() && !Mage::getStoreConfig('inventoryplus/barcode/createbarcode_afterdelivery'))
            return;
        /*Michael 201602 - update attribute to product that is using be barcode - single barcode*/
        $sqlNews = array();
        $codeArr = array();
        $productId = $observer->getEvent()->getProductId();
        if(!Mage::helper('inventorybarcode')->isMultipleBarcode()) {
            $barcodeAtt = Mage::getStoreConfig('inventoryplus/barcode/barcode_attribute');
            $product = Mage::getModel('catalog/product')->load($productId);
            if($product->getData($barcodeAtt)){
                $barcode = $product->getData($barcodeAtt);
            }else {
                $barcode = Mage::helper('inventorybarcode')->generateCode(Mage::getStoreConfig('inventoryplus/barcode/pattern'));
                $checkBarcodeExist = Mage::getModel('catalog/product')->getCollection()
                    ->addAttributeToFilter($barcodeAtt, $barcode)
                    ->setPageSize(1)->setCurPage(1)->getFirstItem();
                while ($checkBarcodeExist->getId()) {
                    $barcode = Mage::helper('inventorybarcode')->generateCode(Mage::getStoreConfig('inventoryplus/barcode/pattern'));
                    $checkBarcodeExist = Mage::getModel('catalog/product')->getCollection()
                        ->addAttributeToFilter($barcodeAtt, $barcode)
                        ->setPageSize(1)->setCurPage(1)->getFirstItem();
                }
                $product->setData($barcodeAtt, $barcode);
                try {
                    $product->save();
                } catch (Exception $e) {
                    Mage::log($e->getMessage(), null, 'inventory_management.log');
                }
            }
            $codeArr['barcode'] = $barcode;
        }

        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');

        $tablename = 'inventorybarcode/barcode';

        $results = Mage::helper('inventorybarcode/attribute')->getAllColumOfTable($tablename);

        $columns = array();
        $string = '';
        $type = '';

        foreach ($results as $result) {
            $fields = explode('_', $result);
            if ($fields[0] == 'barcode' || $fields[0] == 'qty')
                continue;
            foreach ($fields as $id => $field) {
                if ($id == 0)
                    $type = $field;
                if ($id == 1) {
                    $string = $field;
                }
                if ($id > 1)
                    $string = $string . '_' . $field;
            }
            $columns[] = array($type => $string);
            $string = '';
            $type = '';
        }

        $warehouseIds = $observer->getEvent()->getWarehouseId();
        $purchaseOrderId = $observer->getEvent()->getPurchaseOrderId();
        $productId = $observer->getEvent()->getProductId();

        $purchaseOrderProduct = Mage::getModel('inventorypurchasing/purchaseorder_product')->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('purchase_order_id', $purchaseOrderId)
                ->setPageSize(1)->setCurPage(1)->getFirstItem();
//        if ($purchaseOrderProduct->getId() && $purchaseOrderProduct->getBarcode()) {
//            return;
//        }

        $purchaseOrder = Mage::getModel('inventorypurchasing/purchaseorder')->load($purchaseOrderId);


        $suppliererId = $purchaseOrder->getSupplierId();


        $codeArr['purchaseorder_purchase_order_id'] = $purchaseOrder->getId();
        $codeArr['supplier_supplier_id'] = $suppliererId;
        $codeArr['barcode_status'] = 1;
        $codeArr['warehouse_warehouse_id'] = $warehouseIds;

        //auto generate barcode
        if(!$codeArr['barcode'])
            $codeArr['barcode'] = Mage::helper('inventorybarcode')->generateCode(Mage::getStoreConfig('inventoryplus/barcode/pattern'));


        $sqlNews['barcode'] = $codeArr['barcode'];
        $sqlNews['barcode_status'] = $codeArr['barcode_status'];
        $sqlNews['qty'] = $observer->getEvent()->getQtyReceived();

        $delivery = $observer->getEvent()->getDelivery();
        $delivery->setBarcode($codeArr['barcode']);
        $delivery->save();

        foreach ($columns as $id => $column) {
            $i = 0;
            $columnName = '';

            foreach ($column as $id => $key) {
                if ($i == 0)
                    $columnName = $id . '_' . $key;
                if ($i > 0)
                    $columnName = $columnName . '_' . $key;

                $i++;
            }

            if ($id != 'custom') {
                $return = Mage::helper('inventorybarcode')->getValueForBarcode($id, $key, $productId, $codeArr);
                if (is_array($return)) {
                    foreach ($return as $columns) {
                        foreach ($columns as $column => $value) {
                            if (!isset($sqlNews[$id . '_' . $column])) {
                                $sqlNews[$id . '_' . $column] = $value;
                            } else {
                                $sqlNews[$id . '_' . $column] .= ',' . $value;
                            }
                        }
                    }
                } else {
                    $sqlNews[$columnName] = $return;
                }
            }
        }
        $sqlNews['created_date'] = now();
        $sqlNews['qty_original'] = $observer->getEvent()->getQtyReceived();

        if(Mage::helper('inventorybarcode')->isMultipleBarcode())
            $writeConnection->insertMultiple($resource->getTableName('inventorybarcode/barcode'), $sqlNews);
        $sqlNews = array();


        try {
            $purchaseOrderProduct->setData('barcode', $codeArr['barcode'])
                    ->save();
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'inventory_management.log');
        }



        $admin = Mage::getModel('admin/session')->getUser()->getUsername();
        //create action log
        Mage::getModel('inventorybarcode/barcode_actionlog')->setData('barcode_action', Mage::helper('inventorybarcode')->__('Barcode "%s" was created automatically for Purchase order id #%s', $codeArr['barcode'], $codeArr['purchaseorder_purchase_order_id']))
                ->setData('created_at', now())
                ->setData('created_by', $admin)
                ->setData('barcode', $codeArr['barcode'])
                ->save();
    }

    /**
     * process delivery_product_grid_after event
     *
     * @return Magestore_Inventorybarcode_Model_Observer
     */
    public function deliveryProductGridAfter($observer) {
        /* Michael 201602 */
        if (Mage::helper('inventorybarcode')->isMultipleBarcode() && !Mage::getStoreConfig('inventoryplus/barcode/createbarcode_afterdelivery'))
            return;
        $grid = $observer->getEvent()->getGrid();
        if (!Mage::helper('inventorybarcode')->isMultipleBarcode()) {
            /* single barcode mode */
            //return;
            $barcodeAtt = Mage::getStoreConfig('inventoryplus/barcode/barcode_attribute');
            $grid->addColumn('barcode', array(
                'header' => Mage::helper('inventorypurchasing')->__('Barcode'),
                'align' => 'left',
                'width' => '150px',
                'index' => 'barcode',
                'type' => 'input',
                'editable' => true,
                'edit_only' => true,
                'filter_condition_callback' => array($this, '_filterBarcode'),
                'renderer' => 'inventorybarcode/adminhtml_barcode_edit_renderer_customsinglebarcode',
            ));
        }else {
            $grid->addColumn('barcode', array(
                'header' => Mage::helper('inventorypurchasing')->__('Barcode'),
                'align' => 'left',
                'width' => '150px',
                'index' => 'barcode',
                'type' => 'input',
                'editable' => true,
                'edit_only' => true,
                'filter_condition_callback' => array($this, '_filterBarcode'),
                'renderer' => 'inventorybarcode/adminhtml_barcode_edit_renderer_custompo',
            ));
        }
    }

    /**
     * process controller_action_predispatch_adminhtml_sales_order_shipment_new event
     *
     * @return Magestore_Inventorybarcode_Model_Observer
     */
    /*
      public function shipmentNew($observer) {
      $block = $observer->getEvent()->getBlock();

      if (get_class($block) == 'Mage_Adminhtml_Block_Sales_Order_Shipment_Create_Items' && $block->getRequest()->getControllerName() == 'sales_order_shipment' && $block->getRequest()->getActionName() == 'new') {
      $block->setTemplate('inventorybarcode/shipment/sales/order/shipment/create/items.phtml');
      }
      if (get_class($block) == 'Mage_Adminhtml_Block_Sales_Items_Renderer_Default' && $block->getRequest()->getControllerName() == 'sales_order_shipment' && $block->getRequest()->getActionName() == 'new') {
      $block->setTemplate('inventorybarcode/shipment/sales/order/shipment/create/items/renderer/default.phtml');
      }
      if (get_class($block) == 'Mage_Bundle_Block_Adminhtml_Sales_Order_Items_Renderer' && $block->getRequest()->getControllerName() == 'sales_order_shipment' && $block->getRequest()->getActionName() == 'new') {
      $block->setTemplate('inventorybarcode/bundle/sales/shipment/create/items/renderer.phtml');
      }

      }
     *
     */

    /**
     * process sales_order_shipment_save_after event
     *
     * @return Magestore_Inventorybarcode_Model_Observer
     */
    public function shipmentAfterSave($observer) {
        try {
            if (Mage::registry('INVENTORY_BARCODE_ORDER_SHIPMENT'))
                return;
            Mage::register('INVENTORY_BARCODE_ORDER_SHIPMENT', true);
            $barcodeData = array();
            $data = Mage::app()->getRequest()->getParams();

            $shipment = $observer->getEvent()->getShipment();
            $orderId = $shipment->getOrder()->getId();
            foreach ($shipment->getAllItems() as $_item) {

                $item = Mage::getModel('sales/order_item')->load($_item->getOrderItemId());
                if (!isset($data['warehouse-shipment']['items'][$item->getItemId()])) {
                    continue;
                }
                if (in_array($item->getProductType(), array('bundle', 'grouped', 'virtual', 'downloadable')))
                    continue;

                $productId = $item->getProductId();
                if ($item->getProductType() == 'configurable') {
                    $itemData = unserialize($item->getData('product_options'));
                    $productSku = $itemData['simple_sku'];
                    $productId = Mage::getModel('catalog/product')->getIdBySku($productSku);
                }
                //row_total_incl_tax       
                $barcodeData[$item->getItemId()]['qty'] = '';

                if ($item->getParentItemId()) {

                    if (isset($data['shipment']['items'][$item->getParentItemId()])) {

                        $item_parrent = Mage::getModel('sales/order_item')->load($item->getParentItemId());
                        $options = $item->getProductOptions();
                        if (isset($options['bundle_selection_attributes'])) {

                            $option = unserialize($options['bundle_selection_attributes']);

                            $parentQty = $data['shipment']['items'][$item->getParentItemId()];

                            $itemQty = (int) $option['qty'] * (int) $parentQty;

                            $barcodeData[$item->getItemId()]['qty'] = $itemQty;
                            if (isset($data['barcode-shipment']['items'][$item->getItemId()]))
                                $barcodeData[$item->getItemId()]['barcode_id'] = $data['barcode-shipment']['items'][$item->getItemId()];

                            $barcodeData[$item->getItemId()]['warehouse_id'] = $data['warehouse-shipment']['items'][$item->getItemId()];
                            $barcodeData[$item->getItemId()]['product_id'] = $productId;
                            $barcodeData[$item->getItemId()]['item_id'] = $item->getItemId();
                            $barcodeData[$item->getItemId()]['order_id'] = $orderId;
                        }else {
                            $barcodeData[$item->getItemId()]['qty'] = $data['shipment']['items'][$item->getParentItemId()];
                            if (isset($data['barcode-shipment']['items'][$item->getParentItemId()]))
                                $barcodeData[$item->getItemId()]['barcode_id'] = $data['barcode-shipment']['items'][$item->getParentItemId()];
                            $barcodeData[$item->getItemId()]['warehouse_id'] = $data['warehouse-shipment']['items'][$item->getItemId()];
                            $barcodeData[$item->getItemId()]['product_id'] = $productId;
                            $barcodeData[$item->getItemId()]['item_id'] = $item->getItemId();
                            $barcodeData[$item->getItemId()]['order_id'] = $orderId;
                        }
                    } else {
                        $barcodeData[$item->getItemId()]['qty'] = $data['shipment']['items'][$item->getItemId()];
                        if (isset($data['barcode-shipment']['items'][$item->getItemId()]))
                            $barcodeData[$item->getItemId()]['barcode_id'] = $data['barcode-shipment']['items'][$item->getItemId()];
                        $barcodeData[$item->getItemId()]['warehouse_id'] = $data['warehouse-shipment']['items'][$item->getItemId()];
                        $barcodeData[$item->getItemId()]['product_id'] = $productId;
                        $barcodeData[$item->getItemId()]['item_id'] = $item->getItemId();
                        $barcodeData[$item->getItemId()]['order_id'] = $orderId;
                    }
                } else {


                    if (isset($data['shipment']['items'][$item->getItemId()])) {
                        $barcodeData[$item->getItemId()]['qty'] = $data['shipment']['items'][$item->getItemId()];
                        if (isset($data['barcode-shipment']['items'][$item->getItemId()]))
                            $barcodeData[$item->getItemId()]['barcode_id'] = $data['barcode-shipment']['items'][$item->getItemId()];
                    }elseif (isset($data['shipment']['items'][$item->getParentItemId()])) {
                        $barcodeData[$item->getItemId()]['qty'] = $data['shipment']['items'][$item->getParentItemId()];
                        if (isset($data['barcode-shipment']['items'][$item->getParentItemId()]))
                            $barcodeData[$item->getItemId()]['barcode_id'] = $data['barcode-shipment']['items'][$item->getParentItemId()];
                    }

                    $barcodeData[$item->getItemId()]['warehouse_id'] = $data['warehouse-shipment']['items'][$item->getItemId()];
                    $barcodeData[$item->getItemId()]['product_id'] = $productId;
                    $barcodeData[$item->getItemId()]['item_id'] = $item->getItemId();
                    $barcodeData[$item->getItemId()]['order_id'] = $orderId;
                }
                if ($barcodeData[$item->getItemId()]['qty'] > ($item->getQtyOrdered() - $item->getQtyRefunded())) {
                    $barcodeData[$item->getItemId()]['qty'] = ($item->getQtyOrdered() - $item->getQtyRefunded());
                }
            }

            foreach ($barcodeData as $_barcodeData) {
                if (!isset($_barcodeData['barcode_id']) || !$_barcodeData['barcode_id'])
                    continue;
                $barcode = Mage::getModel('inventorybarcode/barcode')->load($_barcodeData['barcode_id']);
                $qty = $barcode->getQty() - $_barcodeData['qty'];
                try {
                    $barcode->setQty($qty)->save();
                    $barcodeShipment = Mage::getModel('inventorybarcode/barcode_shipment')->getCollection()
                            ->addFieldToFilter('barcode_id', $_barcodeData['barcode_id'])
                            ->addFieldToFilter('order_id', $_barcodeData['order_id'])
                            ->addFieldToFilter('item_id', $_barcodeData['item_id'])
                            ->addFieldToFilter('product_id', $_barcodeData['product_id'])
                            ->addFieldToFilter('warehouse_id', $_barcodeData['warehouse_id'])
                            ->setPageSize(1)->setCurPage(1)->getFirstItem();
                    if ($barcodeShipment->getId()) {
                        $barcodeShipment->setQtyShipped($barcodeShipment->getQtyShipped() + $_barcodeData['qty'])->save();
                    } else {

                        Mage::getModel('inventorybarcode/barcode_shipment')
                                ->setData('barcode_id', $_barcodeData['barcode_id'])
                                ->setData('order_id', $_barcodeData['order_id'])
                                ->setData('item_id', $_barcodeData['item_id'])
                                ->setData('product_id', $_barcodeData['product_id'])
                                ->setData('warehouse_id', $_barcodeData['warehouse_id'])
                                ->setData('qty_shipped', $_barcodeData['qty'])
                                ->save();
                    }
                } catch (Exception $e) {

                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
            }
        } catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }

    /**
     * process sales_order_creditmemo_save_after event
     *
     * @return Magestore_Inventorybarcode_Model_Observer
     */
    public function orderCreditmemoSaveAfter($observer) {

        if (Mage::registry('INVENTORY_BARCODE_ORDER_CREDITMEMO'))
            return;
        Mage::register('INVENTORY_BARCODE_ORDER_CREDITMEMO', true);

        $data = Mage::app()->getRequest()->getParams();
        $creditmemo = $observer->getCreditmemo();
        $order = $creditmemo->getOrder();
        $inventoryCreditmemoData = array();

        $order_id = $order->getId();
        $creditmemo_id = $creditmemo->getId();

        foreach ($creditmemo->getAllItems() as $creditmemo_item) {

            if (isset($data['creditmemo']['select-warehouse-supplier'][$creditmemo_item->getOrderItemId()]) && $data['creditmemo']['select-warehouse-supplier'][$creditmemo_item->getOrderItemId()] == 2) {
                continue;
            }

            $item = Mage::getModel('sales/order_item')->load($creditmemo_item->getOrderItemId());
            if (in_array($item->getProductType(), array('configurable', 'bundle', 'grouped')))
                continue;


            //row_total_incl_tax  

            if ($item->getParentItemId()) {

                if (isset($data['creditmemo']['items'][$item->getParentItemId()])) {

                    if (isset($data['creditmemo']['select-warehouse-supplier'][$item->getParentItemId()]) && $data['creditmemo']['select-warehouse-supplier'][$item->getParentItemId()] == 2) {
                        continue;
                    }

                    $item_parrent = Mage::getModel('sales/order_item')->load($item->getParentItemId());
                    $options = $item->getProductOptions();
                    if (isset($options['bundle_selection_attributes'])) {
                        $option = unserialize($options['bundle_selection_attributes']);

                        $parentQty = $data['creditmemo']['items'][$item->getParentItemId()]['qty'];
                        $qtyRefund = (int) $option['qty'] * (int) $parentQty;
                        $qtyShipped = $item->getQtyShipped();
                        $qtyRefunded = $item->getQtyRefunded();
                        $qtyOrdered = $item->getQtyOrdered();

                        $inventoryCreditmemoData[$item->getItemId()]['product'] = $item->getProductId();
                        $inventoryCreditmemoData[$item->getItemId()]['item_id'] = $item->getItemId();

                        //////////
                        //if return to stock
                        /*
                         * total qty will be updated if (qtyShipped + qtyRefunded + qtyRefund) > qtyOrdered and will be returned = (qtyShipped + qtyRefunded + qtyRefund) > qtyOrdered
                         * available qty will be returned = qtyRefund
                         */

                        if (isset($data['creditmemo']['items'][$item->getParentItemId()]['back_to_stock'])) {
                            $inventoryCreditmemoData[$item->getItemId()]['warehouse'] = $data['creditmemo']['warehouse-select'][$item->getParentItemId()];

                            $qtyChecked = $qtyShipped + $qtyRefunded + $qtyRefund - $qtyOrdered;
                            if ($qtyChecked >= 0) {
                                $inventoryCreditmemoData[$item->getItemId()]['qty_total'] = $qtyRefund;
                            } else {
                                $inventoryCreditmemoData[$item->getItemId()]['qty_total'] = $qtyOrdered - $qtyShipped + $qtyRefunded;
                            }
                        } else {
                            continue;
                        }

                        //////////
                    } else {

                        $qtyRefund = $data['creditmemo']['items'][$item->getParentItemId()]['qty'];
                        $qtyShipped = $item->getQtyShipped();
                        $qtyRefunded = $item->getQtyRefunded();
                        $qtyOrdered = $item->getQtyOrdered();

                        //////////
                        //if return to stock
                        /*
                         * total qty will be updated if (qtyShipped + qtyRefunded + qtyRefund) > qtyOrdered and will be returned = (qtyShipped + qtyRefunded + qtyRefund) > qtyOrdered
                         * available qty will be returned = qtyRefund
                         */


                        if (isset($data['creditmemo']['items'][$item->getParentItemId()]['back_to_stock'])) {


                            $qtyChecked = $qtyShipped + $qtyRefunded + $qtyRefund - $qtyOrdered;
                            $inventoryCreditmemoData[$item->getItemId()]['warehouse'] = $data['creditmemo']['warehouse-select'][$item->getParentItemId()];
                            if ($qtyChecked >= 0) {
                                $inventoryCreditmemoData[$item->getItemId()]['qty_total'] = $qtyRefund;
                            } else {
                                $inventoryCreditmemoData[$item->getItemId()]['qty_total'] = $qtyOrdered - $qtyShipped + $qtyRefunded;
                            }
                        } else {
                            continue;
                        }


                        $inventoryCreditmemoData[$item->getItemId()]['product'] = $item->getProductId();
                        $inventoryCreditmemoData[$item->getItemId()]['item_id'] = $item->getItemId();
                    }
                } else {

                    $qtyRefund = $data['creditmemo']['items'][$item->getItemId()]['qty'];
                    $qtyShipped = $item->getQtyShipped();
                    $qtyRefunded = $item->getQtyRefunded();
                    $qtyOrdered = $item->getQtyOrdered();

                    //////////
                    //if return to stock
                    /*
                     * total qty will be updated if (qtyShipped + qtyRefunded + qtyRefund) > qtyOrdered and will be returned = (qtyShipped + qtyRefunded + qtyRefund) > qtyOrdered
                     * available qty will be returned = qtyRefund
                     */


                    if (isset($data['creditmemo']['items'][$item->getItemId()]['back_to_stock'])) {


                        $qtyChecked = $qtyShipped + $qtyRefunded + $qtyRefund - $qtyOrdered;
                        if ($qtyChecked >= 0) {
                            $inventoryCreditmemoData[$item->getItemId()]['qty_total'] = $qtyRefund;
                        } else {
                            $inventoryCreditmemoData[$item->getItemId()]['qty_total'] = $qtyOrdered - $qtyShipped + $qtyRefunded;
                        }

                        $inventoryCreditmemoData[$item->getItemId()]['warehouse'] = $data['creditmemo']['warehouse-select'][$item->getItemId()];
                    } else {
                        continue;
                    }
                    $inventoryCreditmemoData[$item->getItemId()]['product'] = $item->getProductId();
                    $inventoryCreditmemoData[$item->getItemId()]['item_id'] = $item->getItemId();
                }
            } else {
                $qtyRefund = $data['creditmemo']['items'][$item->getItemId()]['qty'];
                $qtyShipped = $item->getQtyShipped();
                $qtyRefunded = $item->getQtyRefunded();
                $qtyOrdered = $item->getQtyOrdered();

                //////////
                //if return to stock
                /*
                 * total qty will be updated if (qtyShipped + qtyRefunded + qtyRefund) > qtyOrdered and will be returned = (qtyShipped + qtyRefunded + qtyRefund) > qtyOrdered
                 * available qty will be returned = qtyRefund
                 */

                if (isset($data['creditmemo']['items'][$item->getItemId()]['back_to_stock'])) {

                    $qtyChecked = $qtyShipped + $qtyRefunded + $qtyRefund - $qtyOrdered;
                    if ($qtyChecked >= 0) {
                        $inventoryCreditmemoData[$item->getItemId()]['qty_total'] = $qtyRefund;
                    } else {
                        $inventoryCreditmemoData[$item->getItemId()]['qty_total'] = $qtyOrdered - $qtyShipped + $qtyRefunded;
                    }

                    $inventoryCreditmemoData[$item->getItemId()]['warehouse'] = $data['creditmemo']['warehouse-select'][$item->getItemId()];
                } else {
                    continue;
                }

                $inventoryCreditmemoData[$item->getItemId()]['product'] = $item->getProductId();
                $inventoryCreditmemoData[$item->getItemId()]['item_id'] = $item->getItemId();
            }
        }

        foreach ($inventoryCreditmemoData as $id => $value) {

            try {
                $barcodeShipment = Mage::getModel('inventorybarcode/barcode_shipment')->getCollection()
                        ->addFieldToFilter('order_id', $order_id)
                        ->addFieldToFilter('product_id', $value['product'])
                        ->addFieldToFilter('warehouse_id', $value['warehouse'])
                        ->setPageSize(1)->setCurPage(1)->getFirstItem();
                if ($barcodeShipment->getId()) {
                    if ($value['qty_total'] > ($barcodeShipment->getQtyShipped() + $barcodeShipment->getQtyRefunded())) {
                        $qty = $barcodeShipment->getQtyShipped() + $barcodeShipment->getQtyRefunded();
                    } else {
                        $qty = $value['qty_total'];
                    }
                    $barcodeShipment->setQtyRefunded($barcodeShipment->getQtyRefunded() + $qty)->save();
                    $barcode = Mage::getModel('inventorybarcode/barcode')->load($barcodeShipment->getBarcodeId());
                    $barcode->setQty($barcode->getQty() + $qty)->save();
                }
            } catch (Exception $e) {

                Mage::log($e->getTraceAsString(), null, 'inventory_management.log');
            }
        }
    }

    /**
     * change barcode type: single/multiple barcode
     * Michael 201602
     */

    public function changeBarcodeType(){
        $useMultipleBarcodeNew = Mage::getStoreConfig('inventoryplus/barcode/use_multiple_barcode');
        $useMultipleBarcodeOld = Mage::getModel('admin/session')->getData('inventoryplus_use_multiple_barcode_old');
        Mage::getModel('admin/session')->unset('inventoryplus_use_multiple_barcode_old');
        if($useMultipleBarcodeNew == $useMultipleBarcodeOld)
            return;
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        $readConnection = $resource->getConnection('core_read');

        if($useMultipleBarcodeNew == 0 && $useMultipleBarcodeOld == 1){
            $sql = 'UPDATE ' . $resource->getTableName('inventorybarcode/barcode') . '  SET `qty` = 0 WHERE `qty` != 0;';
            $writeConnection->query($sql);
        }elseif($useMultipleBarcodeNew == 1 && $useMultipleBarcodeOld == 0){
            if(Mage::getStoreConfig('inventoryplus/barcode/update_barcode') == '3')
                return;
            if(Mage::getStoreConfig('inventoryplus/barcode/update_barcode') == '1'){
                $barcodeAtt = Mage::getStoreConfig('inventoryplus/barcode/barcode_attribute');
                $productCollection = Mage::getModel('catalog/product')->getCollection()
                                    ->addAttributeToSelect($barcodeAtt)
                                    ->addAttributeToSelect('sku')
                                    ->addAttributeToSelect('name')
                                    ->addAttributeToSelect('type_id')
                                    ->addAttributeToFilter('type_id', array('nin' => array('configurable', 'bundle', 'grouped')))
                                    ->addAttributeToFilter($barcodeAtt, array('neq'=>''));
                foreach($productCollection as $product){
                    $barcode = $product->getData($barcodeAtt);
//                    $sqlQty = 'SELECT SUM(`total_qty`) as `phy_total_qty` FROM '. $resource->getTableName('inventoryplus/warehouse_product').' WHERE `product_id` = '. $product->getId() .' GROUP BY `product_id`';
//                    $sqlQty = 'SELECT `total_qty`,`warehouse_id` FROM '. $resource->getTableName('inventoryplus/warehouse_product').' WHERE `product_id` = '. $product->getId();
                    $barcodeQty = 0;
                    $warehouseNames = $warehouseIds = '';
                    $results = Mage::getResourceModel('inventoryplus/warehouse_product')->loadByProductId($product->getId());
                    $i = 0;

                    foreach($results as $r){
                        $barcodeQty += $r['total_qty'];
                        $warehouse = Mage::getModel('inventoryplus/warehouse')->load($r['warehouse_id']);
                        if($i>0) {
                            if($warehouse->getWarehouseName())
                                $warehouseNames .= ',' . $warehouse->getWarehouseName();
                            $warehouseIds .= ',' . $r['warehouse_id'];
                        }else {
                            $warehouseIds .= $r['warehouse_id'];
                            if($warehouse->getWarehouseName())
                                $warehouseNames .= $warehouse->getWarehouseName();
                        }
                        $i++;
                    }
                    $checkBarcode = Mage::getModel('inventorybarcode/barcode')->load($barcode, 'barcode');
                    $checkBarcode->setData('barcode', $barcode)
                                 ->setData('barcode_status', '1')
                                 ->setData('warehouse_warehouse_id', $warehouseIds)
                                 ->setData('warehouse_warehouse_name', $warehouseNames)
                                 ->setData('product_entity_id', $product->getId())
                                 ->setData('product_name', $product->getName())
                                 ->setData('product_sku', $product->getSku())
                                 ->setData('qty', $barcodeQty)
                                 ->setData('created_date', now());
                    try{
                        $checkBarcode->save();
                    }catch (Exception $e){
                        Mage::log($e->getMessage(), null, 'inventory_management.log');
                    }
                }
            }elseif(Mage::getStoreConfig('inventoryplus/barcode/update_barcode') == '2'){
                $productCollection = Mage::getModel('catalog/product')->getCollection()
                                        ->addAttributeToSelect('sku')
                                        ->addAttributeToSelect('name')
                                        ->addAttributeToSelect('type_id')
                                        ->addAttributeToFilter('type_id', array('nin' => array('configurable', 'bundle', 'grouped')))                     ;
                foreach($productCollection as $product){
//                    $sqlQty = 'SELECT `total_qty`,`warehouse_id` FROM '. $resource->getTableName('inventoryplus/warehouse_product').' WHERE `product_id` = '. $product->getId();
                    $barcodeQty = 0;
                    $warehouseNames = $warehouseIds = '';
//                    $results = $readConnection->fetchAll($sqlQty);
                    $results = Mage::getResourceModel('inventoryplus/warehouse_product')->loadByProductId($product->getId());
                    $i = 0;

                    foreach($results as $r){
                        $barcodeQty += $r['total_qty'];
                        $warehouse = Mage::getModel('inventoryplus/warehouse')->load($r['warehouse_id']);
                        if($i>0) {
                            if($warehouse->getWarehouseName())
                                $warehouseNames .= ',' . $warehouse->getWarehouseName();
                            $warehouseIds .= ',' . $r['warehouse_id'];
                        }else {
                            $warehouseIds .= $r['warehouse_id'];
                            if($warehouse->getWarehouseName())
                                $warehouseNames .= $warehouse->getWarehouseName();
                        }
                        $i++;
                    }
                    $barcode = Mage::helper('inventorybarcode')->generateCode(Mage::getStoreConfig('inventoryplus/barcode/pattern'));
                    $checkBarcodeExist = Mage::getModel('inventorybarcode/barcode')->load($barcode, 'barcode');
                    while($checkBarcodeExist->getId()){
                        $barcode = Mage::helper('inventorybarcode')->generateCode(Mage::getStoreConfig('inventoryplus/barcode/pattern'));
                        $checkBarcodeExist = Mage::getModel('inventorybarcode/barcode')->load($barcode, 'barcode');
                    }

                    $checkBarcode = Mage::getModel('inventorybarcode/barcode')->load($barcode, 'barcode');
                    $checkBarcode->setData('barcode', $barcode)
                        ->setData('barcode_status', '1')
                        ->setData('warehouse_warehouse_id', $warehouseIds)
                        ->setData('warehouse_warehouse_name', $warehouseNames)
                        ->setData('product_entity_id', $product->getId())
                        ->setData('product_name', $product->getName())
                        ->setData('product_sku', $product->getSku())
                        ->setData('qty', $barcodeQty)
                        ->setData('created_date', now());
                    try{
                        $checkBarcode->save();
                    }catch (Exception $e){
                        Mage::log($e->getMessage(), null, 'inventory_management.log');
                    }
                }
            }
        }
//        zend_debug::dump($observer);
//        die();
    }

    /**
     * change barcode type: single/multiple barcode
     * Michael 201602
     */

    public function saveConfigBefore(){
        $section = Mage::app()->getRequest()->getParam('section');
        if($section != 'inventoryplus')
            return;
        $useMultipleBarcodeOld = Mage::getStoreConfig('inventoryplus/barcode/use_multiple_barcode');
        Mage::getModel('admin/session')->setData('inventoryplus_use_multiple_barcode_old', $useMultipleBarcodeOld);
    }

    /**
     * process deliveried_product_grid_after_created event
     *
     * @return Magestore_Inventorybarcode_Model_Observer
     * Michael 201602
     */
    public function deliveriedProductGridAfterCreated($observer) {
        if (Mage::helper('inventorybarcode')->isMultipleBarcode() && !Mage::getStoreConfig('inventoryplus/barcode/createbarcode_afterdelivery'))
            return;
        $grid = $observer->getEvent()->getGrid();
        $grid->addColumn('barcode', array(
            'header' => Mage::helper('inventorybarcode')->__('Barcode'),
            'align' => 'left',
            'width' => '150px',
            'index' => 'barcode',
            'type' => 'text',
            'renderer' => 'inventorybarcode/adminhtml_barcode_edit_renderer_barcodedelivery',
        ));
    }

    /**
     * process add_more_button_delivery event
     *
     * @return Magestore_Inventorybarcode_Model_Observer
     * Michael 201602
     */
    public function addMoreButtonDelivery($observer) {
        if (Mage::helper('inventorybarcode')->isMultipleBarcode() && !Mage::getStoreConfig('inventoryplus/barcode/createbarcode_afterdelivery'))
            return;
        $grid = $observer->getEvent()->getGrid();
        $grid->setChild('print_barcode_inventory_delivery_button', $grid->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label' => Mage::helper('inventorypurchasing')->__('Print barcode'),
                'onclick' => 'window.open(\'' . $grid->getUrl('adminhtml/inb_printbarcode/selecttemplate', array('purchaseorder_id' => $grid->getRequest()->getParam('id'))) . '\',\'_blank\', \'scrollbars=yes, resizable=yes, width=750, height=500, left=80, menubar=yes\')',
                //'class' => 'add',
                'style' => 'float:right'
            ))
        );
    }

    /**
     * process add_more_button_delivery_position event
     *
     * @return Magestore_Inventorybarcode_Model_Observer
     * Michael 201602
     */
    public function addMoreButtonDeliveryPosition($observer) {
        if (Mage::helper('inventorybarcode')->isMultipleBarcode() && !Mage::getStoreConfig('inventoryplus/barcode/createbarcode_afterdelivery'))
            return;
        $grid = $observer->getEvent()->getGrid();
        $allButtonShow = $observer->getEvent()->getAllbuttonshow();
        $allButtonShow .= $grid->getChildHtml('print_barcode_inventory_delivery_button');
        $observer->getEvent()->setAllbuttonshow($allButtonShow);
//        return $allButtonShow;
    }
}
