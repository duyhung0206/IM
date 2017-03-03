<?php
class Magestore_Inventorylowstock_Helper_Supplyneeds extends Mage_Core_Helper_Abstract {
	const SUPPLIER_TYPE_LOWEST_COST = 1;
    const SUPPLIER_TYPE_LAST_PURCHASE = 2;
    
    /**
     *
     * @var array
     */
    protected $_forecastFilterSelect;
    

    public function setTopFilter($filter) {
        $this->_forecastFilterSelect = Mage::helper('adminhtml')->prepareFilterString($filter);
    }

    public function getForecastFilterSelect() {
        return $this->_forecastFilterSelect;
    }

    public function getForecastTo() {
        $data = $this->_forecastFilterSelect;
        if (isset($data['forecast_date_to']) && $data['forecast_date_to'])
            return $data['forecast_date_to'];
        return Mage::getModel('core/date')->date('d-m-Y', '+7 days');
    }

    public function getWarehouseSelected() {
        $data = $this->_forecastFilterSelect;
        if (isset($data['warehouse_select']) && $data['warehouse_select'])
            return explode(',', $data['warehouse_select'][0]);
        $warehousesEnable = Mage::helper('inventoryplus/warehouse')->getAllWarehouseNameEnable();
        return array_keys($warehousesEnable);
    }


    public function getHistorySelected() {          
        $data = $this->_forecastFilterSelect;
        if (isset($data['history_select']))
            return $data['history_select'];
        return '30_days';
    }

    public function getHistoryFrom() {
        $data = $this->_forecastFilterSelect;
        if (isset($data['baseon_date_from']))
            return $data['baseon_date_from'];
        return Mage::getModel('core/date')->date('d-m-Y', '-7 days');
    }

    public function getHistoryTo() {       
        $data = $this->_forecastFilterSelect;
        if (isset($data['baseon_date_to']))
            return $data['baseon_date_to'];
        return Mage::getModel('core/date')->date('d-m-Y');
    }

    public function getSalesFromTo() {           
        $data = $this->getHistorySelected();
        $return = array();
        switch ($data) {
            case '7_days':
                $return['from'] = Mage::getModel('core/date')->date('Y-m-d 00:00:00', '-7 days');
                $return['to'] = Mage::getModel('core/date')->date('Y-m-d H:i:s');
                $return['count'] = 7;
                break;
            case '30_days':
                $return['from'] = Mage::getModel('core/date')->date('Y-m-d 00:00:00', '-30 days');
                $return['to'] = Mage::getModel('core/date')->date('Y-m-d H:i:s');
                $return['count'] = 30;
                break;
            case '3_months':
                $return['from'] = Mage::getModel('core/date')->date('Y-m-d 00:00:00', '-3 months');
                $return['to'] = Mage::getModel('core/date')->date('Y-m-d H:i:s');
                $return['count'] = floor((strtotime($return['to']) - strtotime($return['from'])) / (60 * 60 * 24));
                break;
            case 'range':
                $from = $this->getHistoryFrom();
                $to = $this->getHistoryTo();
                $return['from'] = Mage::getModel('core/date')->date('Y-m-d 00:00:00', $from);
                $return['to'] = Mage::getModel('core/date')->date('Y-m-d 23:59:59', $to);
                $return['count'] = floor((strtotime($return['to']) - strtotime($return['from'])) / (60 * 60 * 24));
                break;
            default :
                $return['from'] = Mage::getModel('core/date')->date('Y-m-d 00:00:00', '-30 days');
                $return['to'] = Mage::getModel('core/date')->date('Y-m-d H:i:s');
                $return['count'] = 30;
        }
        return $return;
    }

    public function getNumberDaysForecast() {           
        $return['from'] = Mage::getModel('core/date')->date('Y-m-d 00:00:00');
        $return['to'] = Mage::getModel('core/date')->date('Y-m-d 23:59:59', $this->getForecastTo());
        $return = floor((strtotime($return['to']) - strtotime($return['from'])) / (60 * 60 * 24));
        return $return;
    }

    public function getRatePurchaseMore() {
        return '100';
    }

    public function getWarehouseSales($productId) {//$from,$to){
        $defaultWarehouse = 1; // hard fix for Default warehouse(although could be deleted)
        $warehouseCollection = Mage::getModel('inventoryplus/warehouse')->getCollection();
        $return = array();
        foreach ($warehouseCollection as $warehouse) {
            $warehouseId = $warehouse->getId();
            $collection = Mage::getModel('sales/order_item')->getCollection();
            $collection->getSelect()
                    ->joinLeft(
                            array('warehouse_order' => $collection->getTable('inventoryplus/warehouse_order')), "main_table.item_id=warehouse_order.item_id", array('warehouse_ordered' => new Zend_Db_Expr("IFNULL(warehouse_id, {$defaultWarehouse})")));
            $collection->getSelect()->where("IFNULL(warehouse_id, {$defaultWarehouse}) = {$warehouseId} AND main_table.product_id={$productId}");
            $collection->getSelect()->columns(array('total_ordered' => new Zend_Db_Expr("SUM(qty_ordered)")));
            $return[$warehouseId] =  $collection->setPageSize(1)->setCurPage(1)->getFirstItem()->getTotalOrdered();
        }
        return $return;
    }

    public function getWarehouseSalesRatio($productId) {
        $warehouseSales = $this->getWarehouseSales($productId);
        $total = array_sum($warehouseSales);
        $warehouseCollection = Mage::getModel('inventoryplus/warehouse')->getCollection();
        $return = array();
        foreach ($warehouseCollection as $warehouse) {
            $warehouseId = $warehouse->getId();
            $return[$warehouseId] = $warehouseSales[$warehouseId] / $total;
        }
        return $return;
    }

    public function addPOFromSupplyneedData($warehouseIds, $warehouseNames, $supplierId, $supplierName, $currency, $change_rate) {
        $data['purchase_on'] = now();
        $data['bill_name'] = $this->__('Supply Need');
        ;
        $data['warehouse_id'] = $warehouseIds;
        $data['warehouse_name'] = $warehouseNames;
        $data['supplier_id'] = $supplierId;
        $data['supplier_name'] = $supplierName;
        $data['total_products'] = 0;
        $data['total_amount'] = 0;
        $data['comments'] = 0;
        $data['currency'] = $currency;
        $data['tax_rate'] = 0;
        $data['shipping_cost'] = 0;
        $data['delivery_process'] = 0;
        $data['status'] = 5;
        $data['paid'] = 0;
        $data['total_products_recieved'] = 0;
        $data['created_by'] = 'supplyneeds';
        $data['order_placed'] = '';
        $data['started_date'] = '';
        $data['canceled_date'] = '';
        $data['expected_date'] = '';
        $data['payment_date'] = '';
        $data['ship_via'] = '';
        $data['payment_term'] = '';
        $data['change_rate'] = $change_rate;
        $data['total_product_refunded'] = 0;
        return $data;
    }
    
    public function getProductIdBySql($sku){
        $productCollection = Mage::getResourceModel('catalog/product_collection');
        $productId = $productCollection->addAttributeToSelect('entity_id')
                                ->addAttributeToFilter('sku', $sku)->getColumnValues('entity_id');
        return $productId;
    }
    
    /**
     * 
     * @param array $productData
     * @return array
     */
    public function calculateWarehouseQty(&$productData) {
        $gridExport = Mage::getBlockSingleton('inventorysupplyneeds/adminhtml_inventorysupplyneeds_gridexport');
        $collection = $gridExport->getCollectionData(array_keys($productData));
        $warehouses = $this->getWarehouseSelected();
        $primaryWarehouse = Mage::helper('inventoryplus/warehouse')->getPrimaryWarehouse();
        if (count($collection)) {
            foreach ($collection as $item) {
                $warehouseQty = array();
                $productId = $item->getProductId() ? $item->getProductId() : $this->getProductIdBySql($item->getSku());
                $purchaseQty = $productData[$productId]['purchase_more'];
                foreach ($warehouses as $warehouseId) {
                    $warehouseQty[$warehouseId] = ceil($item->getData('supplyneeds_' . $warehouseId) - $item->getData('in_purchasing_' . $warehouseId));
                    $purchaseQty -= $warehouseQty[$warehouseId];
                }
                //add remaining qty into default warehouse
                if ($purchaseQty > 0) {
                    $defaultWarehouseId = $this->_selectDefaultWarehouse($primaryWarehouse->getId, array_keys($warehouseQty));
                    $warehouseQty[$defaultWarehouseId] += $purchaseQty;
                    $warehouseQty[$defaultWarehouseId] = ($warehouseQty[$defaultWarehouseId] < 0) ? 0 : $warehouseQty[$defaultWarehouseId];
                }
                $productData[$productId]['warehouse_purchase'] = json_encode($warehouseQty);
                //update total purchase qty for each product
                $productData[$productId]['purchase_more'] = array_sum($warehouseQty);
            }
        }
        foreach($productData as &$productItem){
            if(!isset($productItem['warehouse_purchase'])){
                $defaultWarehouseId = $this->_selectDefaultWarehouse($primaryWarehouse->getId(), $warehouses);
                $productItem['warehouse_purchase'] = json_encode(array($defaultWarehouseId=>$productItem['purchase_more']));
            }
        }
        return $productData;
    }
    
    /**
     * Add warehouse statistics (available qty, in purchasing, supply needs,...) to product collection
     * 
     * @param collection $collection
     * @return collection 
     */
    public function addWarehouseStaticToCollection($collection){
        $productIds = array();
        if(count($collection)){
            foreach($collection as $item){
                $productIds[] = $item->getProductId();
            }            
        }
        if(!count($productIds))
            return;
        //get warehouse statistics
        $gridExport = Mage::getBlockSingleton('inventorysupplyneeds/adminhtml_inventorysupplyneeds_gridexport');
        $exportCollection = $gridExport->getCollectionData($productIds); 
        $warehouseStatics = array();
        $attachFields = array('available_qty', 'total_qty_ordered', 'avg_qty_ordered', 'in_purchasing', 'supplyneeds');
        foreach($exportCollection as $exportItem){
            $productId = $exportItem->getProductId() ? $exportItem->getProductId() : $this->getProductIdBySql($exportItem->getSku());;
            foreach($exportItem->getData() as $field=>$value){
                if(in_array($field, $attachFields)) {
                    $warehouseStatics[$productId][$field] = $value;
                }
            }
        }
        //add statistics to collection
        foreach($collection as $item){
            if(isset($warehouseStatics[$item->getProductId()])){
                $item->addData($warehouseStatics[$item->getProductId()]);
            }
        }
        return $collection;
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
}