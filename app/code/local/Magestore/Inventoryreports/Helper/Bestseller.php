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
 * Inventoryreports Helper
 *
 * @category    Magestore
 * @package     Magestore_Inventoryreports
 * @author      Magestore Developer
 */
class Magestore_Inventoryreports_Helper_Bestseller extends Mage_Core_Helper_Abstract {

    /**
     * Get requested time range
     *
     * @param array $requestData
     * @return array
     */
    public function getTimeRange($requestData) {
        return Mage::helper('inventoryreports')->getTimeRange($requestData);
    }
    public function getBestSellerProductCollection($requestData){

        $productCollection = Mage::getResourceModel('reports/product_collection')
           ->addAttributeToSelect('*')
           ->addAttributeToFilter('type_id', array('nin' => array('configurable', 'bundle', 'grouped')))
           ->addOrderedQty()
           //->setOrder('ordered_qty','desc')
                ;

        $productCollection->joinTable(
           'inventorypurchasing/supplier_product',
           'product_id=entity_id',
           array('supplier_id' => 'supplier_id'),
           null,
           'left'
        );
        $productCollection->joinTable(
           'inventoryplus/warehouse_product',
           'product_id=entity_id',
           array('warehouse_id' => 'warehouse_id'),
           null,
           'left'
        );
        $productCollection->joinTable(
           array('catalogproduct'=> $productCollection->getTable('catalog/product')),
           'entity_id=entity_id',
           array('product_sku' => 'sku'),
           null,
           'left'
        );
        if(isset($requestData['select_time'])){
            $dateRange = $this->getTimeRange($requestData);
            $datefrom = $dateRange['from'];
            $dateto = $dateRange['to'];
            $toTimezone = Mage::getSingleton('core/date')->getGmtOffset('hours') . ':00';
            if (Mage::getSingleton('core/date')->getGmtOffset('hours') >= 0)
                $toTimezone = '+' . $toTimezone;
            $fromTimezone = (date("Z") >= 0) ? '+' . date("Z") . ':00' : date("Z") . ':00';
            $productCollection->getSelect()->where("CONVERT_TZ(order_items.created_at,'$fromTimezone','$toTimezone')  >= '$datefrom'");
            $productCollection->getSelect()->where("CONVERT_TZ(order_items.created_at,'$fromTimezone','$toTimezone') <=  '$dateto'" );
        }

        if(isset($requestData['supplier_select']) && ($requestData['supplier_select'] > 0)){
           $productCollection->addFieldToFilter('supplier_id', $requestData['supplier_select']);
        }
        if(isset($requestData['warehouse_select']) && ($requestData['warehouse_select'] > 0)){
           $productCollection->addFieldToFilter('warehouse_id', $requestData['warehouse_select']);
        }
        $productCollection->getSelect()->limit(10);

        return $productCollection;
   }
}