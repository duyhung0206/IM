<?php

require_once("magmi_csvreader.php");
require_once("fshelper.php");

class Magmi_ReStockMagestoreInventory extends Magmi_GeneralImportPlugin {

    public function getPluginInfo() {
        return array("name" => "Update stock in Magestore Inventory Management's Warehouse", "author" => " Magnus ", "version" => "1.0",
            "url" => "https://www.youtube.com/watch?v=7v6A5HOjKSw");
    }

    /* protected function beforeImport()
      {
      $csvFileImported = $this->getCsvImportedFile();
      $this->log("$csvFileImported...", "info");
      return true;
      } */

    public function afterImport() {
        $csvObject = $this->prepareCSV();
        $this->processCsvRows($csvObject);
        return true;
    }
    public function isRunnable()
    {
        return array(FSHelper::getExecMode() != null,"");
    }
    protected function processCsvRows($csvObject) {
        $csvObjectCopy = $this->prepareCSV();
        $warehouseIds = $this->getAllWarehousesInCsv($csvObjectCopy);
        if (!count($warehouseIds)) {  //There is not column like warehouse_1,warehouse_2,... in the csv file
            /* There is no warehouse assigned in csv file */
            $lastAdjustId = array();
            $warehouse = $this->getIsRootWarehouse();
            $warehouseId = $warehouse['warehouse_id'];
            $adjustmentId = $this->createAdjustmentStock($warehouse['warehouse_id'], $warehouse['warehouse_name']);
            while (($item = $csvObject->getNextRecord()) !== false) {   // Read csv file row by row
                //$this->log("SKU {$item['sku']} was processed!", "info");
                if (!isset($item['sku']))
                    continue;
                $sku = $item['sku'];
                $productId = $this->getProductIdFromSku($sku);
                $productType = $this->getProductTypeFromSku($sku);
                if (!in_array($productType, array('simple', 'downloadable', 'virtual')))
                    continue;
                $qty = $item['qty'];
                if ($this->productIsExistInWarehouse($productId, $warehouseId)) {  // Product exist in warehouse
                    $totalAvailQty = $this->getTotalAvailQty($productId);
                    if($qty!=$totalAvailQty){
                        $diffQty = $qty - $totalAvailQty;
                        $warehouseProdData = $this->getWarehouseProdData($warehouseId,$productId);
                        $oldAvailQty = $warehouseProdData['available_qty'];
                        $oldPhysQty = $warehouseProdData['total_qty'];
                        $newAvailQty = $oldAvailQty + $diffQty;
                        $newPhysQty = $oldPhysQty + $diffQty;
                        $this->updateWarehouseProduct($warehouseId, $productId, $newAvailQty, $newPhysQty);
                        $this->createAdjustmentStockProduct($adjustmentId, $productId, $oldAvailQty, $newAvailQty);    
                    }
                }else {      // Product is not exist in warehouse
                    /* Insert new to warehouse product and create adjustment stock product */
                    $physQty = $avalQty = $qty;
                    $this->insertWarehouseProduct($warehouseId, $productId, $physQty, $avalQty);
                    $this->createAdjustmentStockProduct($adjustmentId, $productId, 0, $newAvailQty);
                }
            }
        } else {  // Found assigned warehouses
            $lastAdjustId = array();
            foreach ($warehouseIds as $wId => $wName) {
                $lastId = $this->createAdjustmentStock($wId, $wName);
                $lastAdjustId[$wId] = $lastId;
            }
            while (($item = $csvObject->getNextRecord()) !== false) {   // Read csv file row by row
                //$this->log("SKU {$item['sku']} was processed!", "info");
                if (!isset($item['sku']))
                    continue;
                $sku = $item['sku'];                
                $productId = $this->getProductIdFromSku($sku);
                $productType = $this->getProductTypeFromSku($sku);
                if (!in_array($productType, array('simple', 'downloadable', 'virtual')))
                    continue;
                foreach ($warehouseIds as $warehouseId => $warehouseName) {
                    $adjustmentId = $lastAdjustId[$warehouseId];
                    $newAvailQty = $item['warehouse_' . $warehouseId] ? $item['warehouse_' . $warehouseId] : 0;                    
                    if ($this->productIsExistInWarehouse($productId, $warehouseId)) {  // Product exist in warehouse
                        /* Update warehouse product and create adjustment stock product */
                        $warehouseProdData = $this->getWarehouseProdData($warehouseId,$productId);
                        $oldAvailQty = $warehouseProdData['available_qty'];
                        $oldPhysQty = $warehouseProdData['total_qty'];
                        $newPhysQty = $oldPhysQty + ($newAvailQty - $oldAvailQty);
                        $this->updateWarehouseProduct($warehouseId, $productId, $newAvailQty, $newPhysQty);
                        $this->createAdjustmentStockProduct($adjustmentId, $productId, $oldAvailQty, $newAvailQty);
                        /* Update catalog stock item table */                        
                    } else {      // Product is not exist in warehouse
                        /* Insert new to warehouse product and create adjustment stock product */
                        $physQty = $avalQty = $newAvailQty;
                        $this->insertWarehouseProduct($warehouseId, $productId, $physQty, $avalQty);
                        $this->createAdjustmentStockProduct($adjustmentId, $productId, 0, $newAvailQty);
                    }
                }            
                $this->updateStockItem($productId);
                if(isset($item['suppliers']) && $item['suppliers']){
                    /* Insert products into supplier's product */
                    $this->assignSupplierProduct($productId,$item['suppliers']);
                } 
            }
        }
        return;
    }
    
    protected function getTotalAvailQty($productId){
        $tableOne = $this->tablename('erp_inventory_warehouse_product');
        $sql = 'SELECT SUM(available_qty) as total_avail_qty from ' . $tableOne . ' WHERE product_id = '.$productId;
        $results = $this->selectAll($sql);
        return $results[0]['total_avail_qty'];
    }
    protected function updateWarehouseProduct($warehouseId, $productId, $newAvailQty, $newPhysQty){
        $table = $this->tablename('erp_inventory_warehouse_product');
        $sql = "UPDATE $table SET available_qty=$newAvailQty, total_qty=$newPhysQty WHERE product_id=$productId AND warehouse_id=$warehouseId";
        $this->update($sql);
    }
    protected function createAdjustmentStockProduct($adjustmentId, $productId, $oldAvailQty, $newAvailQty){
        $table = $this->tablename('erp_inventory_adjuststock_product');
        $sql = 'INSERT INTO '.$table . ' (adjuststock_id,product_id,old_qty,suggest_qty,adjust_qty) VALUES ('.$adjustmentId.','.$productId.','.$oldAvailQty.','.$newAvailQty.','.$newAvailQty.');';
        $lasteId = $this->insert($sql);
        return $lasteId;
    }
    protected function updateStockItem($productId){
        $tableOne = $this->tablename('erp_inventory_warehouse_product');
        $sql = 'SELECT SUM(available_qty) as total_avail_qty from ' . $tableOne . ' WHERE product_id = '.$productId;
        $results = $this->selectAll($sql);
        $qty = $results[0]['total_avail_qty'];
        $tableTwo = $this->tablename('cataloginventory_stock_item');
        $sql = "UPDATE $tableTwo SET qty=$qty WHERE product_id=$productId ";
        $this->update($sql);
        return;
    }
    protected function getWarehouseProdData($warehouseId,$productId){
        $table = $this->tablename('erp_inventory_warehouse_product');
        $sql = 'SELECT * from ' . $table . ' WHERE warehouse_id = '.$warehouseId.' AND product_id = '.$productId;
        $results = $this->selectAll($sql);
        return $results[0];
    }
    protected function insertWarehouseProduct($warehouseId,$product_id,$physQty,$avalQty){
        $table = $this->tablename('erp_inventory_warehouse_product');
        $sql = 'INSERT INTO '.$table . ' (warehouse_id,product_id,total_qty,available_qty) VALUES ('.$warehouseId.','.$product_id.','.$physQty.','.$avalQty.');';
        $lasteId = $this->insert($sql);
        return $lasteId;
    }
    protected function productIsExistInWarehouse($product_id,$warehouseId){
        $table = $this->tablename('erp_inventory_warehouse_product');
        $firstSql = 'SELECT * from ' . $table . ' WHERE warehouse_id = '.$warehouseId.' AND product_id = '.$product_id;
        $result = $this->selectone($firstSql, null,'warehouse_product_id');
        if(count($result))return true;
        else return false;
    }
    protected function getAllWarehousesInCsv($csvObject){
        $warehouseIds = $this->getAllInventoryWarehouses();
        $return = array();
        $item = $csvObject->getNextRecord();
        if(!isset($item['sku']))return;
        foreach ($warehouseIds as $key => $w){
            foreach ($item as $row => $rvalue){
                if(isset($item['warehouse_'.$w['warehouse_id']])){
                     $return[(int)$w['warehouse_id']] = $w['warehouse_name'];
                }
            }
        }
        return $return;
    }
    protected function createAdjustmentStock($warehouseId,$warehouseName){
        $table = $this->tablename('erp_inventory_adjuststock');
        $createdAt = date('Y-m-d H:i:s');
        $sql = "INSERT INTO $table (warehouse_id, warehouse_name, reason,created_by,created_at,confirmed_by,confirmed_at,status)
VALUES ($warehouseId, '$warehouseName', 'Stock Ajustment available qty by Magmi','Magmi','$createdAt','Magmi','$createdAt',1) ";
        $lasteId = $this->insert($sql);
        return $lasteId;
    }
    protected function getProductTypeFromSku($sku){
        $t = $this->tablename('catalog_product_entity');
        $result = $this->selectone("SELECT type_id FROM $t WHERE sku='".$sku."'", null,'type_id');
        return $result;
    }
    protected function getProductIdFromSku($sku){
        $t = $this->tablename('catalog_product_entity');
        $result = $this->selectone("SELECT entity_id FROM $t WHERE sku='".$sku."'", null,'entity_id');
        return $result;
    }
    protected function getAllInventoryWarehouses(){
        $t = $this->tablename('erp_inventory_warehouse');
        $results = $this->selectAll("SELECT warehouse_id,warehouse_name FROM $t");
        return $results;
    }
    protected function getIsRootWarehouse(){
        $t = $this->tablename('erp_inventory_warehouse');
        $results = $this->selectAll("SELECT warehouse_id,warehouse_name FROM $t WHERE is_root=1");
        if(count($results)){
            $results = $this->selectAll("SELECT warehouse_id,warehouse_name FROM $t LIMIT 1");
        }
        return $results[0];
    }        
    protected function getCsvImportedFile() {
        $eng = $this->_callers[0];
        $ds = $eng->getPluginInstanceByClassName("datasources", "Magmi_CSVDataSource");
        if ($ds != null) {
            $csvfile = $ds->getParam("CSV:filename");
            return $csvfile;
        }
        return;
    }
    protected function getCsvImportedParams() {
        $eng = $this->_callers[0];
        $ds = $eng->getPluginInstanceByClassName("datasources", "Magmi_CSVDataSource");
        if ($ds != null) {
            return $ds->getParams();
        }
        return array();
    }
    private function prepareCSV()
    {
        $defaultParams = $this->getParams();
        $csvParams = $this->getCsvImportedParams();
        $params = array_merge($defaultParams, $csvParams);
        $csvreader = new Magmi_CSVReader();
        $csvreader->initialize($params, 'CSV');
        $csvreader->checkCSV();
        $csvreader->openCSV();
        $csvreader->getColumnNames();
        return $csvreader;
    }
    protected function productIsAssignedToSupplier($product_id,$supplierId){
        $table = $this->tablename('erp_inventory_supplier_product');
        $firstSql = 'SELECT * from ' . $table . ' WHERE supplier_id = '.$supplierId.' AND product_id = '.$product_id;
        $result = $this->selectone($firstSql, null,'supplier_product_id');
        if(count($result))return true;
        else return false;
    }
    protected function assignSupplierProduct($productId,$supplierStr){
		$suppliers = explode(",",$supplierStr);
		$table = $this->tablename('erp_inventory_supplier_product');
		foreach($suppliers as $supplierId){
			if(!$this->productIsAssignedToSupplier($productId,$supplierId)){					
        		$sql = 'INSERT INTO '.$table . ' (supplier_id,product_id) VALUES ('.$supplierId.','.$productId.');';
        		$lasteId = $this->insert($sql);	
			}	
		}
	}
}