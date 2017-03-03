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
 * @category 	Magestore
 * @package 	Magestore_Inventorysupplier
 * @copyright 	Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license 	http://www.magestore.com/license-agreement.html
 */

/**
 * Inventorysupplier Resource Collection Model
 * 
 * @category 	Magestore
 * @package 	Magestore_Inventorysupplier
 * @author  	Magestore Developer
 */
class Magestore_Inventorypurchasing_Model_Mysql4_Purchaseorder_Productwarehouse_Collection extends Magestore_Inventoryplus_Model_Mysql4_Collection_Abstract {

    public function _construct() {
        parent::_construct();
        $this->_init('inventorypurchasing/purchaseorder_productwarehouse');
    }
    
    public function getItemsBySupplierId($supplierId, $firstDate) {
        $this->addFieldToSelect(array('purchase_order_id', 'warehouse_id', 'product_id', 'qty_order', 'product_name', 'product_sku'));
        $this->getSelect()
                ->joinLeft(
                        array('purchaseorder' => $this->getTable('inventorypurchasing/purchaseorder')), "main_table.purchase_order_id = purchaseorder.purchase_order_id", array('purchase_on'));
        $this->addFieldToFilter('purchase_on', array('gteq' => $firstDate));
        $this->getSelect()->where("`purchaseorder`.`supplier_id`= $supplierId");
        $this->getSelect()->columns(array('date_without_hour' => 'date(`purchase_on`)'));
        return $this;
    }

}
