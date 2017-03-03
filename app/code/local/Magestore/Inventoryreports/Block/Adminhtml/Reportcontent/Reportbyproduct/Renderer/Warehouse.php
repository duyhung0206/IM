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
 * @package     Magestore_Inventorysupplyneeds
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * Inventoryreports Adminhtml Block
 * 
 * @category    Magestore
 * @package     Magestore_Inventoryreports
 * @author      Magestore Developer
 */
class Magestore_Inventoryreports_Block_Adminhtml_Reportcontent_Reportbyproduct_Renderer_Warehouse extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {

    public function render(Varien_Object $row) 
    {
        $time = '';
        $count = 0;
        $totalTime = 0;
        $now = time(); // or your date as well
        
        $block = new Magestore_Inventoryreports_Block_Adminhtml_Supplier_Product_Grid;        
        $filter   = $block->getParam($block->getVarNameFilter(), null);
        $condorder = '';
        if($filter){
            $data = $this->helper('adminhtml')->prepareFilterString($filter);
            foreach($data as $value=>$key){
                if($value == 'supplier_id'){
                    $condorder = $key;
                }                
            }
        }
        
        if(Mage::helper('core')->isModuleEnabled('Magestore_Inventorybarcode')){
            $purchaseOrderIds = array();
            $collection = Mage::getModel('inventorybarcode/barcode')->getCollection();
            $collection->addFieldToFilter('product_entity_id',$row->getId());
            $collection->addFieldToFilter('qty',array(
                'gteq'=>0
            ));
            $collection->groupBy('warehouse_warehouse_id');
            $warehouseIds = array();
            if($collection->getSize()){
                foreach($collection as $result){
                    $warehouseId = $result->getWarehouseWarehouseId();
                    $warehouseId = explode(',',$warehouseId);
                    foreach($warehouseId as $wId)
                        if(!in_array($wId,$warehouseIds))
                            $warehouseIds[] = $wId;
                }
            }
            $warehouses = Mage::getModel('inventoryplus/warehouse')
                                            ->getCollection()
                                            ->addFieldToFilter('warehouse_id',array('in'=>$warehouseIds));
            $count += $warehouses->getSize();
            if($count == 0)
                return $this->__('N/A');
            $resultWarehouse = '';
            foreach($warehouses as $warehouse)
                //$resultWarehouse .= $warehouse->getWarehouseName().'<br />';
                $resultWarehouse .= "<a href=\"#\" onclick=\"showWarehouseInventory(".$warehouse->getId().",".$row->getId().");return false;\" title=\"".Mage::helper('inventoryreports')->__('Report Time Inventory by Warehouse')."\">".$warehouse->getWarehouseName()."<a/>"."<br/>";
            return $resultWarehouse;
        }
    }
}