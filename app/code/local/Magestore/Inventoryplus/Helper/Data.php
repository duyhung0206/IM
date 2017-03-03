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
 * Inventory Helper
 * 
 * @category    Magestore
 * @package     Magestore_Inventory
 * @author      Magestore Developer
 */
class Magestore_Inventoryplus_Helper_Data extends Mage_Core_Helper_Abstract {

    /**
     * Check if a product qty is allowed to use decimals
     * 
     * @param type $productId
     * @return boolean
     */
    public function isQtyUsesDecimals($productId) {
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
        $manageStock = $stockItem->getManageStock();
        if ($stockItem->getUseConfigManageStock()) {
            $manageStock = Mage::getStoreConfig('cataloginventory/item_options/manage_stock', Mage::app()->getStore()->getStoreId());
        }
        if ($manageStock) {
            $isQtyDecimal = $stockItem->getIsQtyDecimal();
            if ($isQtyDecimal == 1) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * Check if magento product data are installed to warehouse_product
     * 
     * @return int
     */
    public function isDataInstalled() {
        $check = Mage::getModel('inventoryplus/install')
                ->getCollection()
                ->setPageSize(1)
                ->setCurPage(1)
                ->getFirstItem();
        if ($check->getStatus() != 1) {
            $isInsertData = Mage::getModel('inventoryplus/checkupdate')
                    ->getCollection()
                    ->setPageSize(1)
                    ->setCurPage(1)
                    ->getFirstItem()
                    ->getIsInsertData();
            if ($isInsertData != 1) {
                return 0;
            } else {
                $check->setStatus(1);
                try {
                    $check->save();
                } catch (Exception $e) {
                    throw $e;
                }
            }
        }
        return 1;
    }

    /**
     * Get manage stock from a productId
     * 
     * @param int $productId
     * @return manage stock
     */
    public function getManageStockOfProduct($productId) {
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
        $manageStock = $stockItem->getManageStock();
        if ($stockItem->getUseConfigManageStock()) {
            $manageStock = Mage::getStoreConfig('cataloginventory/item_options/manage_stock', Mage::app()->getStore()->getStoreId());
        }
        return $manageStock;
    }

    /**
     * Get ordered qty from order item
     * 
     * @param \Mage_Sales_Model_Order_Item $orderItem
     * @return int|float
     */
    public function getQtyOrderedFromOrderItem($orderItem) {
        $qtyOrdered = 0;
        if ($orderItem->getParentItemId()) {
            $parentOrderItem = Mage::getModel('sales/order_item')->load($orderItem->getParentItemId());
            if ($parentOrderItem && $parentOrderItem->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                $qtyOrdered = $parentOrderItem->getQtyOrdered();
            }
        }
        $qtyOrdered = $qtyOrdered ? $qtyOrdered : $orderItem->getQtyOrdered();

        return $qtyOrdered;
    }

    /**
     * Check if the page in Inventory section
     * 
     * @return boolean
     */
    public function isInInventorySection() {
        if (Mage::app()->getRequest()->getParam('inventoryplus') == '1') {
            return true;
        }
        return false;
    }

    /**
     * Update layout of inventory configuration page
     * 
     * @param Mage_Adminhtml_Controller_Action $controller
     */
    public function updateConfigLayout($controller, $layout) {
        $request = $controller->getRequest();
        $fullRequest = $controller->getFullActionName();
        $applied = false;
        if ($fullRequest == 'adminhtml_system_config_edit' && $request->getParam('section') == 'inventoryplus')
            $applied = true;
        if ($fullRequest == 'adminhtml_sales_order_shipment_new' && $request->getParam('inventoryplus') == '1')
            $applied = true;
        if ($fullRequest == 'adminhtml_sales_order_shipment_view' && $request->getParam('inventoryplus') == '1')
            $applied = true;

        if ($applied) {
            $layout->getUpdate()->addHandle('adminhtml_inventoryplus_layout');
        }

        if ($fullRequest == 'adminhtml_sales_order_view' && $request->getParam('inventoryplus') == '1')
            $layout->getUpdate()->addHandle('adminhtml_ins_sales_order_view');
    }

    /**
     * Get list of countries
     * 
     * @return type
     */
    public function getCountryList() {
        $result = array();
        $collection = Mage::getModel('directory/country')->getCollection();
        foreach ($collection as $country) {
            $cid = $country->getId();
            $cname = $country->getName();
            $result[$cid] = $cname;
        }
        return $result;
    }

    /**
     * get list of warehouse
     * @return type
     */
    public function getWarehouseList() {
        $options = array();
        $warehouses = Mage::getModel('inventoryplus/warehouse')->getCollection();
        foreach ($warehouses as $warehouse) {
            $options[$warehouse->getId()] = $warehouse->getWarehouseName();
        }

        return $options;
    }

    /**
     * get warehouse options
     * 
     * @return type
     */
    public function getWarehouseOptions() {
        $options = array();
        $options[] = array('value' => 0, 'label' => $this->__('Select Warehouse'), 'object' => null);
        $warehouses = Mage::getModel('inventoryplus/warehouse')->getCollection();
        foreach ($warehouses as $warehouse) {
            $options[] = array('value' => $warehouse->getId(), 'label' => $warehouse->getWarehouseName(), 'object' => $warehouse);
        }

        return $options;
    }

    /**
     * Check is Warehouse plugin exists and enabled in global config.
     * 
     * @return boolean
     */
    public function isWarehouseEnabled() {
        return Mage::helper('core')->isModuleEnabled('Magestore_Inventorywarehouse');
    }

    /**
     * get label status
     * @return string
     */
    public function getStatusLabel($status) {
        $return = $this->__('Pending');
        if ($status == 1) {
            $return = $this->__('Completed');
        }
        if ($status == 3) {
            $return = $this->__('Processing');
        }
        return $return;
    }

    public function filterDates($array, $dateFields) {
        if (empty($dateFields)) {
            return $array;
        }
        $filterInput = new Zend_Filter_LocalizedToNormalized(array(
            'date_format' => Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT)
        ));
        $filterInternal = new Zend_Filter_NormalizedToLocalized(array(
            'date_format' => Varien_Date::DATE_INTERNAL_FORMAT
        ));

        foreach ($dateFields as $dateField) {
            if (array_key_exists($dateField, $array) && !empty($dateField)) {
                $array[$dateField] = $filterInput->filter($array[$dateField]);
                $array[$dateField] = $filterInternal->filter($array[$dateField]);
            }
        }
        return $array;
    }

    public function getAllocatedQty($item, $warehouseId) {
        $product_id = $item->getEntityId();
        return Mage::getResourceModel('inventoryplus/warehouse_order')->getOnHoldQty($product_id, $warehouseId);
    }

    public function getPermission($warehouseId, $permissionType) {
        $admin = Mage::getSingleton('admin/session')->getUser();
        $result = Mage::getResourceModel('inventoryplus/warehouse_permission')
                ->getPermission($admin->getId(), $warehouseId);
        $permission = isset($result[$permissionType]) ? $result[$permissionType] : 0;
        return $permission;
    }

    public function saveSessionPermission() {
        Mage::getSingleton('adminhtml/session')->setData('inventory_permission', null);
        $permissionArray = array();
        $adminPermission = Mage::getModel('inventoryplus/warehouse_permission')
                ->getCollection();
        foreach ($adminPermission as $permission) {
            if (!array_key_exists($permission->getAdminId(), $permissionArray)) {
                $permissionArray[$permission->getAdminId()] = array();
            }
            if (!array_key_exists($permission->getWarehouseId(), $permissionArray[$permission->getAdminId()])) {
                $permissionArray[$permission->getAdminId()][$permission->getWarehouseId()] = '';
            }
            if ($permissionArray[$permission->getAdminId()][$permission->getWarehouseId()] == '') {
                $permissionArray[$permission->getAdminId()][$permission->getWarehouseId()]['can_edit'] = !$permission->getCanEdit() ? '0' : $permission->getCanEdit();
                $permissionArray[$permission->getAdminId()][$permission->getWarehouseId()]['can_adjust'] = !$permission->getCanAdjust() ? '0' : $permission->getCanAdjust();
                $permissionArray[$permission->getAdminId()][$permission->getWarehouseId()]['can_send_request_stock'] = !$permission->getCanSendRequestStock() ? '0' : $permission->getCanSendRequestStock();
                $permissionArray[$permission->getAdminId()][$permission->getWarehouseId()]['can_physical'] = !$permission->getCanPhysical() ? '0' : $permission->getCanPhysical();
                $permissionArray[$permission->getAdminId()][$permission->getWarehouseId()]['can_purchase_product'] = !$permission->getCanPurchaseProduct() ? '0' : $permission->getCanPurchaseProduct();
            }
        }
        Mage::getSingleton('adminhtml/session')->setData('inventory_permission', $permissionArray);
    }

    /**
     * Get available warehouses of product
     * 
     * @param string $productId
     * @return array
     */
    public function getAvailableWarehouses($productId) {
        $list = array();
        $warehouseProducts = Mage::getModel('inventoryplus/warehouse_product')
                ->getCollection()
                ->addFieldToFilter('product_id', $productId)
        //->addFieldToFilter('available_qty', array('gt' => 0))
        ;
        foreach ($warehouseProducts as $warehouseProduct) {
            $list[$warehouseProduct->getWarehouseId()]['qty'] = $warehouseProduct->getAvailableQty();
        }
        $warehouses = Mage::getModel('inventoryplus/warehouse')
                ->getCollection()
                ->addFieldToFilter('warehouse_id', array('in' => array_keys($list)));
        if ($warehouses->getSize()) {
            foreach ($warehouses as $warehouse) {
                $list[$warehouse->getId()] = $warehouse->getData();
                unset($list[$warehouse->getId()]['usersale']);
            }
            arsort($list);
        }
        return $list;
    }
    
    /**
     * 
     * @param int $product_id
     * @param float $qty
     * @return int
     */
    public function selectWarehouseToShip($product_id, $qty)
    {
        $warehouse = Mage::getModel('inventoryplus/warehouse_product')
                ->getCollection()
                ->addFieldToFilter('product_id', $product_id)
                ->addFieldToFilter('total_qty', array('gteq' => $qty))
                ->setPageSize(1)
                ->setCurPage(1)
                ->getFirstItem();

        return $warehouse->getWarehouseId();
    }    

    /**
     * Check shipment when create invoice
     * 
     * @param array $items
     * @return bool
     */
    public function checkShipment($items)
    {
        foreach($items as $item){
            $orderItem = $item->getOrderItem();
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($orderItem->getProductId());
            
            if (!$stockItem->getManageStock()) {
                continue;
            }
            
            if (in_array($orderItem->getProductType(), array('configurable', 'bundle', 'grouped', 'virtual', 'downloadable'))) {
                continue; 
            }
            
            if ($this->selectWarehouseToShip($orderItem->getProductId(), $item->getQty()) == 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get version of WebPOS extension
     * 
     * @return string
     */
    public function getWebPOSVersion() {
        if (Mage::helper('core')->isModuleEnabled('Magestore_Webpos')) {
            return (string) Mage::getConfig()->getModuleConfig('Magestore_Webpos')->version;
        }
        return null;
    }

    /**
     * Get version of Inventory WebPOS extension
     * 
     * @return string
     */
    public function getInventoryWebPOSVersion() {
        if (Mage::helper('core')->isModuleEnabled('Magestore_Inventorywebpos')) {
            return (string) Mage::getConfig()->getModuleConfig('Magestore_Inventorywebpos')->version;
        }
        return null;
    }

    /**
     * Check if WebPOS is active with version from 2.0
     * 
     * @return string
     */
    public function isWebPOS20Active() {
        if (((int) str_replace('.', '', $this->getWebPOSVersion())) >= 20) {
            return true;
        }
        return false;
    }

    /**
     * Check if Inventory WebPOS is active with version from 1.1
     * 
     * @return string
     */
    public function isInventoryWebPOS11Active() {
        if (((int) str_replace('.', '', $this->getInventoryWebPOSVersion())) >= 11) {
            return true;
        }
        return false;
    }

    /**
     * Get module edition
     * 
     * @return string
     */
    public function getEdition() {
        return (string) Mage::getConfig()->getModuleConfig('Magestore_Inventoryplus')->edition;
    }

    /**
     * Get current user name
     * 
     * @return string
     */
    public function getCurrentUser() {
        $user = new Varien_Object(array('username' => ''));
        if ($adminUser = Mage::getModel('admin/session')->getUser()) {
            $user->setUsername($adminUser->getUsername());
        }
        Mage::dispatchEvent('inp_get_current_username', array('user' => $user));
		if($user->getUsername())
			return $user->getUsername();
		else
			return $this->__('POS system or Others');
    }

    public function isNullOrEmptyString($str) {
        return (!isset($str) || trim($str) === '');
    }

    /**
     * Convert a value from current currency to base currency
     *
     * @param $amount
     * @return mixed
     */
    public function convertToBaseCurrency($amount) {
        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        return Mage::helper('directory')->currencyConvert($amount, $currentCurrencyCode, $baseCurrencyCode);
    }

    /**
     * Convert a value from base currency to current currency
     *
     * @param $amount
     * @return mixed
     */
    public function convertToCurrentCurrency($amount) {
        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();
        return Mage::helper('directory')->currencyConvert($amount, $baseCurrencyCode, $currentCurrencyCode);
    }

    /**
     * Parses the string into variables
     * 
     * @param string $str
     * @param array $arr
     */
    public function parseStr($str, array &$arr = null) {
        return parse_str($str, $arr);
    }

    /**
     * 
     * @param string $data
     * @return string
     */
    public function base64Decode($data, $strict = false) {
        return base64_decode($data, $strict);
    }

    /**
     * 
     * @param string $data
     * @return string
     */
    public function base64Encode($data) {
        return base64_encode($data);
    }

    /**
     * 
     * @param string $filename
     * @return string
     */
    public function fileGetContents($filename, $use_include_path = false, $context = null, $offset = -1, $maxlen = null) {
        return file_get_contents($filename, $use_include_path, $context, $offset, $maxlen);
    }

    /**
     * 
     * @param string $filename
     * @param string $data
     * @return int
     */
    public function filePutContents($filename, $data, $flags = 0, $context = null) {
        return file_put_contents($filename, $data, $flags, $context);
    }

    /**
     * 
     * @param string $filename
     * @param string $mode
     * @return bool
     */
    public function chMod($filename, $mode) {
        chmod($filename, $mode);
    }

    /**
     * 
     * @param string $filename
     * @param string $context
     * @return bool
     */
    public function unLink($filename, $context = null) {
        return unlink($filename, $context);
    }

    /**
     * 
     * @param int $ascii
     * @return string
     */
    public function cHR($ascii) {
        return chr($ascii);
    }

    /**
     * 
     * @param string $url
     * @return resource a cURL handle on success, <b>FALSE</b> on errors.
     */
    public function curlInit($url = null) {
        return curl_init($url);
    }

    /**
     * 
     * @return bool
     */
    public function curlSetopt($ch, $option, $value) {
        return curl_setopt($ch, $option, $value);
    }

    /**
     * 
     * @return mixed
     */
    public function curlExec($ch) {
        return curl_exec($ch);
    }

    public function curlClose($ch) {
        curl_close($ch);
    }

    public function fOpen($filename, $mode, $use_include_path = false, $context = null) {
        return fopen($filename, $mode, $use_include_path, $context);
    }

    public function fWrite($handle, $string, $length = null) {
        return fwrite($handle, $string, $length);
    }

    /**
     * 
     * @param string $filename
     * @return bool
     */
    public function isFile($filename) {
        return is_file($filename);
    }

    /**
     * 
     * @param string $filename
     * @return bool
     */
    public function isDir($filename) {
        return is_dir($filename);
    }

    public function mkDir($pathname, $mode = 0777, $recursive = false, $context = null) {
        return mkdir($pathname, $mode, $recursive, $context);
    }

    public function moveUploadedFile($filename, $destination) {
        return move_uploaded_file($filename, $destination);
    }

    /**
     * 
     * @param string $path
     * @param string $suffix
     * @return string
     */
    public function baseName($path, $suffix = null) {
        return basename($path, $suffix);
    }

    /**
     * 
     * @param string $str
     * @return string
     */
    public function stripSlashes($str) {
        return stripslashes($str);
    }


    public function getUploadFile()
    {
        return $_FILES;
    }
    /**
     *
     * @param string $filename
     * @return bool
     */
    public function fileExists($filename){
        return file_exists($filename);
    }

    /**
     *
     * @param string $filename
     * @return bool
     */
    public function openDir($filename){
        return opendir($filename);
    }

    public function imagePng($image, $filename = null, $quality = null, $filters = null){
        return imagepng ($image, $filename, $quality, $filters);
    }
            
}
