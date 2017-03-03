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
 * Inventorybarcode Helper
 * 
 * @category    Magestore
 * @package     Magestore_Inventorybarcode
 * @author      Magestore Developer
 */
class Magestore_Inventorybarcode_Helper_Data extends Mage_Core_Helper_Data {

    const MULTIPLE_BARCODE_CONFIG_XML_PATH = 'inventoryplus/barcode/use_multiple_barcode';
    const BARCODE_ATTRIBUTE_CONFIG_XML_PATH = 'inventoryplus/barcode/barcode_attribute';

    /**
     * Check if using multiple barcode
     * 
     * @return boolean
     */
    public function isMultipleBarcode() {
        if (Mage::getStoreConfig(self::MULTIPLE_BARCODE_CONFIG_XML_PATH) == '0')
            return false;
        return true;
    }

    public function getBarcodeAttribute() {
        return Mage::getStoreConfig(self::BARCODE_ATTRIBUTE_CONFIG_XML_PATH);
    }

    /**
     * Get product Id from Barcode
     * 
     * @param srting $barcode
     * @return int
     */
    public function getProductIdByBarcode($barcode) {
        $productId = null;
        if (Mage::getStoreConfig(self::MULTIPLE_BARCODE_CONFIG_XML_PATH) == '0') {
            $barcodeAttribute = Mage::getStoreConfig(self::BARCODE_ATTRIBUTE_CONFIG_XML_PATH);
            $product = Mage::getModel('catalog/product')
                    ->getCollection()
                    ->addAttributeToFilter($barcodeAttribute, $barcode)
                    ->getFirstItem();
            $productId = $product->getId();
        } else {
            $barcode = Mage::getModel('inventorybarcode/barcode')->load($barcode, 'barcode');
            $productId = $barcode->getProductEntityId();
        }
        return $productId;
    }

    /**
     * Get barcodes from product Id
     * 
     * @param int $productId
     * @return array
     */
    public function getBarcodeByProductId($productId) {
        $barcodes = array();
        if (Mage::getStoreConfig(self::MULTIPLE_BARCODE_CONFIG_XML_PATH) == '0') {
            $barcodeAttribute = Mage::getStoreConfig(self::BARCODE_ATTRIBUTE_CONFIG_XML_PATH);
            $product = Mage::getModel('catalog/product')
                    ->getCollection()
                    ->addAttributeToSelect($barcodeAttribute)
                    ->addFieldToFilter('entity_id', $productId)
                    ->getFirstItem();
            $barcodes[] = $product->getData($barcodeAttribute);
        } else {
            $barcodes = Mage::getModel('inventorybarcode/barcode')->getBarcodeByProductId($productId);
        }
        return $barcodes;
    }

    /**
     * Get barcodes from item Id
     * 
     * @param int $itemId
     * @return array
     */
    public function getBarcodeByItemId($itemId) {
        $barcodes = array();
        $itemCollection = Mage::getModel('sales/order_item')
                ->getCollection()
                ->addFieldToFilter('parent_item_id', $itemId);
        if ($itemCollection->getSize()) {
            foreach ($itemCollection as $item) {
                $barcodes = array_unique(array_merge($barcodes, $this->getBarcodeByProductId($item->getProductId())));
            }
        } else {
            $item = Mage::getModel('sales/order_item')->load($itemId);
            $barcodes = $this->getBarcodeByProductId($item->getProductId());
        }
        return $barcodes;
    }

    /**
     * get Warehouse list
     * 
     * return Array
     */
    public function getWarehouseList() {
        $warehouses = Mage::getModel('inventoryplus/warehouse')->getCollection()
                ->setOrder('warehouse_name', 'ASC');
        $values = array();
        $values[0] = $this->__('Choose Warehouse');
        foreach ($warehouses as $warehouse) {
            $values[$warehouse->getId()] = $warehouse->getWarehouseName();
        }

        return $values;
    }

    /**
     * get Supplier list
     * 
     * return Array
     */
    public function getSupplierList() {
        $suppliers = Mage::getModel('inventorypurchasing/supplier')->getCollection()
                ->setOrder('supplier_name', 'ASC');
        $values = array();
        $values[0] = $this->__('Choose Supplier');
        foreach ($suppliers as $supplier) {
            $values[$supplier->getId()] = $supplier->getSupplierName();
        }

        return $values;
    }

    /**
     * get validate for barcode field
     * 
     * return String
     */
    public function getBarcodeName() {
        $barcodeType = Mage::getStoreConfig('inventoryplus/barcode/barcode_type');
        $barcodes = Mage::getModel('inventorybarcode/barcodetypes')->toOptionArray();
        $return = '';

        foreach ($barcodes as $id => $name) {
            if ($name['value'] == $barcodeType) {
                $return = $name['label'];
            }
        }

        return $return;
    }

    /**
     * get validate for barcode field
     * 
     * return String
     */
    public function getValidateBarcode() {
//        $barcodeType = Mage::getStoreConfig('inventoryplus/barcode/barcode_type');
//        $return = '';
//        switch ($barcodeType){
//            case 'code128':
//                $return = 'required-entry';
//                break;
//            case 'upc':
//                $return = 'required-entry validate-number validate-length minimum-length-12 maximum-length-12';
//                break;
//            case 'ean':
//                $return = 'required-entry validate-number validate-length minimum-length-8 maximum-length-14';
//                break;
//            case 'code39':
//                $return = 'required-entry';
//                break;
//            case 'interleaved-2-of-5':
//                $return = 'required-entry validate-number';
//                break;
//            case 'codabar':
//                $return = 'required-entry';
//                break;
//            default:
        $return = 'required-entry';
//                break;
//        }

        return $return;
    }

    /**
     * get Purchase Order list
     * 
     * return Array
     */
    public function getPurchaseOrderList() {
        $purchaseorders = Mage::getModel('inventorypurchasing/purchaseorder')->getCollection()
                ->setOrder('purchase_order_id', 'DESC');
        $values = array();
        $values[0] = $this->__('Choose Purchase Order');
        foreach ($purchaseorders as $purchaseorder) {
            $values[$purchaseorder->getId()] = $this->__('PO #,%s', $purchaseorder->getId());
        }

        return $values;
    }

    /**
     * get value for barcode
     * 
     * param String $table, String $column, int $productId, array $data
     * return Array
     */
    public function getValueForBarcode($table, $column, $productId, $data) {

        if ($table == 'product') {

            $model = Mage::getModel('catalog/product')->load($productId);
            return $model->getData($column);
        }

        if ($table == 'warehouse') {
            $array = array();
            foreach ($data['warehouse_warehouse_id'] as $data['warehouse_warehouse_id']) {
                $model = Mage::getModel('inventoryplus/warehouse')->load($data['warehouse_warehouse_id']);
                $array[] = array($column => $model->getData($column));
            }
            return $array;
        }
        if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorypurchasing')) {
            if ($table == 'supplier') {

                $model = Mage::getModel('inventorypurchasing/supplier')->load($data['supplier_supplier_id']);
                return $model->getData($column);
            }

            if ($table == 'purchaseorder') {

                $model = Mage::getModel('inventorypurchasing/purchaseorder')->load($data['purchaseorder_purchase_order_id']);
                return $model->getData($column);
            }
        }
    }

    public function generateCode($string) {
        $barcode = preg_replace_callback('#\[([AN]{1,2})\.([0-9]+)\]#', array($this, 'convertExpression'), $string);
        $checkBarcodeExist = Mage::getModel('inventorybarcode/barcode')->load($barcode, 'barcode');

        if ($checkBarcodeExist->getId()) {
            $barcode = $this->generateCode($string);
        }

        return $barcode;
    }

    public function convertExpression($param) {
        $alphabet = (strpos($param[1], 'A')) === false ? '' : 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $alphabet .= (strpos($param[1], 'N')) === false ? '' : '0123456789';
        return $this->getRandomString($param[2], $alphabet);
    }

    public function importProduct($data) {

        if (count($data)) {
            Mage::getModel('admin/session')->setData('barcode_product_import', $data);
            Mage::getModel('admin/session')->setData('null_barcode_product_import', 0);
        } else {
            Mage::getModel('admin/session')->setData('null_barcode_product_import', 1);
            Mage::getModel('admin/session')->setData('barcode_product_import', null);
        }
    }

    public function selectboxBarcodeByPid($productId, $orderItemId, $orderId = null, $_warehouseId = null, $creditmemo = null) {

        if ($_warehouseId == null) {
            $warehouseOrder = Mage::getModel('inventoryplus/warehouse_order')->getCollection()
                    ->addFieldToFilter('order_id', $orderId)
                    ->addFieldToFilter('product_id', $productId);

            $allWarehouse = Mage::helper('inventoryplus/warehouse')->getAllWarehouseNameEnable();
            $warehouseProductModel = Mage::getModel('inventoryplus/warehouse_product')->getCollection()
                    ->addFieldToFilter('product_id', $productId)
                    ->setOrder('total_qty', 'DESC');
            $warehouseHaveProduct = array();
            $_warehouseId = 0;
            $firstWarehouse = $warehouseOrder->getFirstItem()->getWarehouseId();
            foreach ($warehouseProductModel as $model) {
                $warehouseId = $model->getWarehouseId();
                if (!isset($allWarehouse[$warehouseId]))
                    continue;
                $warehouseName = $allWarehouse[$warehouseId];
                if ($warehouseName != '') {
                    if ($warehouseId == $firstWarehouse) {
                        $_warehouseId = $warehouseId;
                    }
                }
            }
        }


        if (!$creditmemo) {
            $return = "<select class='warehouse-shipment' name='barcode-shipment[items][$orderItemId]' onchange='changebarcode(this,$orderItemId);' id='barcode-shipment[items][$orderItemId]'>";
        } else {

            $return = '<label id="creditmemo[barcode-label][' . $orderItemId . ']">' . $this->__('Barcode') . '</label>';
            $return .= '<select id="creditmemo[barcode-select][' . $orderItemId . ']" name="creditmemo[barcode-select][' . $orderItemId . ']">';
        }

        $barcodes = Mage::getModel('inventorybarcode/barcode')->getCollection()
                ->addFieldToFilter('product_entity_id', $productId)
                ->addFieldToFilter('barcode_status', 1)
                ->addFieldToFilter('qty', array('gt' => 0));
        $i = 0;
        $return .= "<option value=''>" . $this->__('Select Barcode') . "</option>";
        foreach ($barcodes as $barcode) {
            $barcodeWarehouseId = explode(',', $barcode->getWarehouseWarehouseId());

            if ($_warehouseId && !in_array($_warehouseId, $barcodeWarehouseId)) {
                continue;
            } else {


                $return .= "<option value='" . $barcode->getId() . "' ";
                $return .= ">" . $barcode->getBarcode() . "(" . $barcode->getQty() . " product(s))</option>";
                $i++;
            }
        }
        $return .= "</select><br />";
        if ($i == 0) {
            $return = '<label id="creditmemo[barcode-label][' . $orderItemId . ']"></label><div id="creditmemo[barcode-select][' . $orderItemId . ']">' . $this->__('No barcode of this item found!') . '</div>';
        }


        return $return;
    }

    /*
     * 
     * get barcode for order refund
     * 
     * @return String
     */

    public function getCreditmemoBarcode($productId, $orderItemId, $orderId = null, $_warehouseId = null) {

        $barcodeShipment = Mage::getModel('inventorybarcode/barcode_shipment')->getCollection()
                        ->addFieldToFilter('order_id', $orderId)
                        ->addFieldToFilter('item_id', $orderItemId)
                        ->addFieldToFilter('product_id', $productId)
                        ->addFieldToFilter('warehouse_id', $_warehouseId)->getFirstItem();

        if ($barcodeShipment->getId()) {
            $barcode = Mage::getModel('inventorybarcode/barcode')->load($barcodeShipment->getBarcodeId());

            $return = '<label id="creditmemo[barcode-label][' . $orderItemId . ']">' . $barcode->getBarcode() . '</label>';
            $return .= '<input id="creditmemo[barcode-value][' . $orderItemId . ']" type="hidden" name="creditmemo[barcode-value][' . $orderItemId . ']" value="' . $barcode->getId() . '"/>';
        } else {
            $return = '<label id="creditmemo[barcode-label][' . $orderItemId . ']"></label><div id="creditmemo[barcode-select][' . $orderItemId . ']">' . $this->__('No barcode of this item found!') . '</div>';
        }


        return $return;
    }

    public function getDataTemplateBarcode($templateId) {
        $data = array();
        $barcodeTemplates = Mage::getModel('inventorybarcode/barcodetemplate')->load($templateId);
        if ($barcodeTemplates) {
            $data['page_height'] = (int) $barcodeTemplates->getData('page_height');
            $data['barcode_per_row'] = (int) $barcodeTemplates->getData('barcode_per_row');
            $data['veltical_distantce'] = $barcodeTemplates->getData('veltical_distantce');
            $data['horizontal_distance'] = $barcodeTemplates->getData('horizontal_distance');
            $data['page_width'] = $barcodeTemplates->getData('page_width');
            $data['productname_show'] = $barcodeTemplates->getData('productname_show');
            $data['sku_show'] = $barcodeTemplates->getData('sku_show');
            $data['price_show'] = $barcodeTemplates->getData('price_show');
            $data['top_margin'] = $barcodeTemplates->getData('top_margin');
            $data['left_margin'] = $barcodeTemplates->getData('left_margin');
            $data['right_margin'] = $barcodeTemplates->getData('right_margin');
            $data['bottom_margin'] = $barcodeTemplates->getData('bottom_margin');
            $data['barcode_type'] = $barcodeTemplates->getData('barcode_type');
            $data['barcode_width'] = $barcodeTemplates->getData('barcode_width');
            $data['barcode_height'] = $barcodeTemplates->getData('barcode_height');
            $data['font_size'] = $barcodeTemplates->getData('font_size');
            if ($barcodeTemplates->getData('barcode_unit') == '0') {
                $data['barcode_unit'] = 'mm';
            } elseif ($barcodeTemplates->getData('barcode_unit') == '1') {
                $data['barcode_unit'] = 'cm';
            } elseif ($barcodeTemplates->getData('barcode_unit') == '2') {
                $data['barcode_unit'] = 'in';
            }
        }
        return $data;
    }

    /**
     * Load product barcode data
     * 
     * @param array $barcodeIds
     * @return array
     */
    public function loadProductBarcodeData($barcodeIds) {
        $productBarcodeData = array('barcodes' => array(), 'products' => array());
        $productIds = array();
        if ($this->isMultipleBarcode()) {
            /* multiple barcode mode */
            $barcodes = Mage::getResourceModel('inventorybarcode/barcode_collection')
                    ->addFieldToFilter('barcode_id', array('in' => $barcodeIds));
            if (count($barcodes)) {
                foreach ($barcodes as $barcodeObj) {
                    $productBarcodeData['barcodes'][$barcodeObj->getId()] = $barcodeObj->getBarcode();
                    $productIds[$barcodeObj->getProductEntityId()] = $barcodeObj->getId();
                    $barcodeProductArr[$barcodeObj->getId()] = $barcodeObj->getProductEntityId();
                }
            }


            $products = Mage::getResourceModel('catalog/product_collection')
                    ->addFieldToFilter('entity_id', array('in' => array_keys($productIds)))
                    ->addAttributeToSelect(array('name', 'price'));
            if (count($products)) {
                foreach ($products as $product) {
                    foreach ($barcodeProductArr as $key => $pId) {
                        if ($product->getId() == $pId) {
                            $barcodeId = $key;
                            $productBarcodeData['products'][$barcodeId] = $product;
                        }
                    }
//                    $barcodeId = $productIds[$product->getId()];
//                    $productBarcodeData['products'][$barcodeId] = $product;
                }
            }
        } else {
            /* single barcode mode */
            $products = Mage::getResourceModel('catalog/product_collection')
                    ->addFieldToFilter('entity_id', array('in' => $barcodeIds))
                    ->addAttributeToSelect(array('name', 'price', $this->getBarcodeAttribute()));
            if (count($products)) {
                foreach ($products as $product) {
                    $barcodeId = $product->getId();
                    $productBarcodeData['barcodes'][$barcodeId] = $product->getData($this->getBarcodeAttribute());
                    $productBarcodeData['products'][$barcodeId] = $product;
                }
            }
        }
        return $productBarcodeData;
    }
    
    public function updateTemplateBarcode() {
        $model = Mage::getModel('inventorybarcode/barcodetemplate');
            
            $template1.='<div id="mydiv" style=" border: 4px solid #C99D6C; overflow: hidden; width:120mm; float:left; padding:0; margin-top: 10px;">';
                for($i = 0; $i<3; $i++){
                    $template1.='<table style="border-spacing:0; width:117mm; height:30mm; margin:0 0;text-align: center; overflow: hidden">';
                    $template1.='<tr>';
                    for($j=0;$j<3 ;$j++){
                        $template1.='<td  align ="center" style="vertical-align: middle; overflow: hidden;white-space: nowrap; border: 1px solid black; width:39mm; height:16mm ">';
                        $template1.='<img style=" float: left;width:39mm; height:16mm;" name="barcode_images" src="{{media url="/inventorybarcode/source/barcode.png"}}" />';
                        $template1.='</td>';  
                    }
                    $template1.='</tr>';
                    $template1.='</table>';
                }
            $template1.='</div>';
            
            $template2.='<div id="mydiv" style=" border: 4px solid #C99D6C; overflow: hidden; width:120mm; float:left; padding:0; margin-top: 10px;">';
                for($i = 0; $i<3; $i++){
                    $template2.='<table style="border-spacing:0; width:117mm; height:30mm; margin:0 0;text-align: center; overflow: hidden">';
                    $template2.='<tr>';
                    for($j=0;$j<3 ;$j++){
                        $template2.='<td  align ="center" style="vertical-align: middle; overflow: hidden;white-space: nowrap; border: 1px solid black; width:39mm; height:18.4mm ">';
                        $template2.= '<span style=" width:100%;float: left; font-size:2.4mm; text-align: center; ">Product Name</span>';
                        $template2.='<img style=" float: left;width:39mm; height:16mm;" name="barcode_images" src="{{media url="/inventorybarcode/source/barcode.png"}}" />';
                        $template2.='</td>';

                    }
                    $template2.='</tr>';
                    $template2.='</table>';
                }
            $template2.='</div>';
            
            $template3.='<div id="mydiv" style=" border: 4px solid #C99D6C; overflow: hidden; width:120mm; float:left; padding:0; margin-top: 10px;">';
                for($i = 0; $i<3; $i++){
                    $template3.='<table style="border-spacing:0; width:117mm; height:30mm; margin:0 0;text-align: center; overflow: hidden">';
                    $template3.='<tr>';
                    for($j=0;$j<3 ;$j++){
                        $template3.='<td  align ="center" style="vertical-align: middle; overflow: hidden;white-space: nowrap; border: 1px solid black; width:39mm; height:20.8mm ">';
                        $template3.= '<span style=" width:100%;float: left; font-size:2.4mm; text-align: center; ">Product Name</span>';
                        $template3.= '<span style=" width:100%;float: left; font-size:2.4mm; text-align: center; ">Price</span>';
                        $template3.='<img style=" float: left;width:39mm; height:16mm;" name="barcode_images" src="{{media url="/inventorybarcode/source/barcode.png"}}" />';
                        $template3.='</td>';

                    }
                    $template3.='</tr>';
                    $template3.='</table>';
                }
            $template3.='</div>';
            
            $template4.='<div id="mydiv" style=" border: 4px solid #C99D6C; overflow: hidden; width:120mm; float:left; padding:0; margin-top: 10px;">';
                for($i = 0; $i<3; $i++){
                    $template4.='<table style="border-spacing:0; width:117mm; height:30mm; margin:0 0;text-align: center; overflow: hidden">';
                    $template4.='<tr>';
                    for($j=0;$j<3 ;$j++){
                        $template4.='<td  align ="center" style="vertical-align: middle; overflow: hidden;white-space: nowrap; border: 1px solid black; width:39mm; height:20.8mm ">';
                        $template4.= '<span style=" width:100%;float: left; font-size:2.4mm; text-align: center; ">Product Name</span>';
                        $template4.= '<span style=" width:100%;float: left; font-size:2.4mm; text-align: center; ">Product Sku</span>';
                        $template4.='<img style=" float: left;width:39mm; height:16mm;" name="barcode_images" src="{{media url="/inventorybarcode/source/barcode.png"}}"/>';
                        $template4.='</td>';

                    }
                    $template4.='</tr>';
                    $template4.='</table>';
                }
            $template4.='</div>';
        

            $template5 = '<div style="width: 80mm; text-align: center; ">                                                     
                                <table id ="kai" style=" width : 80; height:20; line-height:0.3; ">                                                    
                                        <tr width = 80mm>                                                    
                                                <td id="kai" width = 40mm>                                                    
                                                        <span style="float: left; width: 20mm; font-size: 10px; text-align: left; margin-left: 14px;">Product Name</span></br>                                                    
                                                        <span style="float: left; width: 20mm; font-size: 10px; text-align: left; margin-left: 14px;">Product Sku</span> </br>                            
                                                        <span style="float: left; width: 20mm; font-size: 12px; text-align: left; margin-left: 14px;">Price</span>                          
                                                </td>                                                    
                                                <td id="kai"  style="line-height: 0.5; " >                                                    
                                                        <img style="width: 100px;" src="{{media url="/inventorybarcode/source/barcode.jpg"}}"/></br></br>                                                    
                                                        <span style="float: left; text-align: left; margin-left: 5px;  font-size: 10px;">010091930191421</span>                                                   
                                                </td>                                                    
                                        </tr>                                                    
                                </table>                                                
                        </div>';

            $model->setId(1)->setBarcodeTemplateName("Barcode (3 items per row)")->setHtml($template1)->save();
            $model->setId(2)->setBarcodeTemplateName("Name, Barcode (3 items per row)")->setHtml($template2)->save();
            $model->setId(3)->setBarcodeTemplateName("Name, Price, Barcode (3 items per row)")->setHtml($template3)->save();
            $model->setId(4)->setBarcodeTemplateName("Name, Sku, Barcode (3s item per row)")->setHtml($template4)->save();
            $model->setId(5)->setBarcodeTemplateName("Barcode for jewelry ")->setHtml($template5)->save();
    }

    /* check qty barcode exist*/
    public function checkQtyBarcode($product_id){
 
        $warehouse_product = Mage::getModel('inventoryplus/warehouse_product')
            ->getCollection()
            ->addFieldToFilter('product_id', $product_id);


        $barcode_product = Mage::getModel('inventorybarcode/barcode')
            ->getCollection()
            ->addFieldToFilter('product_entity_id', $product_id);

        $warehouse_product->getSelect()->columns(
            array(
                'qty_product' => new Zend_Db_Expr("SUM(main_table.total_qty)")
            )
        );
        $warehouse_product->groupByProductId();
        
        $barcode_product->getSelect()->columns(
            array(
                'qty_barcode' => new Zend_Db_Expr("SUM(main_table.qty)")
            )
        );
        $barcode_product->groupByBarcodeId();
        
        $qtyProduct = 0;
        $qtyBarcode = 0;
        foreach ($warehouse_product as $productqty){
            $qtyProduct = $productqty->getQtyProduct();
        }
        foreach ($barcode_product as $barcodeqty){
            $qtyBarcode = $barcodeqty->getQtyBarcode();
        }
		
		/* Other way by Magnus
		$qtyProduct = $warehouse_product->getFirstItem()->getQtyProduct();
		$qtyBarcode = $barcode_product->getFirstItem()->getQtyBarcode();  */

        return $qtyProduct - $qtyBarcode;
    }
   

    /*get qty barcode not exist follow warehouse*/
    public function getQtyBarcore($product_id, $warehouse_id)
    {
        $warehouse_product = Mage::getModel('inventoryplus/warehouse_product')
            ->getCollection()
            ->addFieldToFilter('product_id', $product_id);


        $barcode_product = Mage::getModel('inventorybarcode/barcode')
            ->getCollection()
            ->addFieldToFilter('product_entity_id', $product_id);

        if(!$warehouse_id){
            $warehouse_product->getSelect()->columns(
                array(
                    'qty_product' => new Zend_Db_Expr("SUM(main_table.total_qty)")
                )
            );
            $warehouse_product->groupByProductId();
            
            $barcode_product->getSelect()->columns(
                array(
                    'qty_barcode' => new Zend_Db_Expr("SUM(main_table.qty)")
                )
            );
            $barcode_product->groupByBarcodeId();
        }else{
            $warehouse_product->getSelect()->columns(
                array(
                    'qty_product' => new Zend_Db_Expr("SUM(main_table.total_qty)")
                )
            )->where("warehouse_id = '$warehouse_id'");
            $warehouse_product->groupByProductId();
            
            $barcode_product->getSelect()->columns(
                array(
                    'qty_barcode' => new Zend_Db_Expr("SUM(main_table.qty)")
                )
            )->where("warehouse_warehouse_id = '$warehouse_id'");
            $barcode_product->groupByBarcodeId();
        }
        
        $qtyProduct = 0;
        $qtyBarcode = 0;
        
        foreach ($warehouse_product as $productqty){
            $qtyProduct = $productqty->getQtyProduct();
        }
        foreach ($barcode_product as $barcodeqty){
            $qtyBarcode = $barcodeqty->getQtyBarcode();
        }
		/* Other way by Magnus
		$qtyProduct = $warehouse_product->getFirstItem()->getQtyProduct();
		$qtyBarcode = $barcode_product->getFirstItem()->getQtyBarcode();  */
		
        $qtyBarcode = (($qtyProduct - $qtyBarcode) > 0 ? ($qtyProduct - $qtyBarcode): 0 );
        return $qtyBarcode;
    }

}
