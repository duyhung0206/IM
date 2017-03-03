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
 * Inventorysupplier Resource Model
 * 
 * @category 	Magestore
 * @package 	Magestore_Inventorysupplier
 * @author  	Magestore Developer
 */
class Magestore_Inventorylowstock_Model_Mysql4_Notificationlog extends Mage_Core_Model_Mysql4_Abstract
{
	public function _construct(){
		$this->_init('inventorylowstock/notificationlog', 'notification_log_id');
	}
	
    public function selectsql($productId){ 
		$read = $this->_getReadAdapter();
		$exclusionSelect = $read->select()
			->from(array('ts' => Mage::getSingleton('core/resource')->getTableName("erp_inventory_outofstock_tracking")), array('*'))
			->where('product_id = ?',$productId);
		//return $read->fetchAll($exclusionSelect);
		return $query = $this->_getReadAdapter()->query($exclusionSelect);
	}
	
	
}