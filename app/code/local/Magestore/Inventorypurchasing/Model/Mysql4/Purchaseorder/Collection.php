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
class Magestore_Inventorypurchasing_Model_Mysql4_Purchaseorder_Collection extends Magestore_Inventoryplus_Model_Mysql4_Collection_Abstract {

    public function _construct() {
        parent::_construct();
        $this->_init('inventorypurchasing/purchaseorder');
    }
    
    /**
     * 
     * @param int $supplierId
     * @param string $firstDate
     * @return \Magestore_Inventorypurchasing_Model_Mysql4_Purchaseorder_Collection
     */
    public function getPOBySupplierDate($supplierId, $firstDate) {
        $this->addFieldToFilter('purchase_on', array('gteq' => $firstDate))
                ->addFieldToFilter('supplier_id', array('eq' => $supplierId));
        $this->getSelect()->columns(array('date_without_hour' => 'date(`purchase_on`)'));
        $this->getSelect()->columns(array('cost_purchase_by_day' => 'sum(`main_table`.`total_amount`)'));
        $this->getSelect()->group(array('date(`purchase_on`)'));
        return $this;
    }

}
