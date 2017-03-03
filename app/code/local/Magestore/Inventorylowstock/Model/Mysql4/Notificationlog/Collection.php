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
class Magestore_Inventorylowstock_Model_Mysql4_Notificationlog_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	public function _construct(){
		parent::_construct();
		$this->_init('inventorylowstock/notificationlog');
	}
	
	public function getOutOfStockProducts() {
		$products = Mage::getModel('catalog/product')
			->getCollection()
			->addAttributeToSelect('entity_id')
			->joinField(
				'is_in_stock', 'cataloginventory/stock_item', 'is_in_stock', 'product_id=entity_id', '{{table}}.stock_id=1', 'left'
			)
			->addAttributeToFilter('is_in_stock', array('eq' => 0));
		return $products;
	}
	public function sqlcollection(){
            
		$coreResource = Mage::getSingleton('core/resource');
		$writeConnection = $coreResource->getConnection('core_write');
		$listOosProducts = $this->getOutOfStockProducts();
		$listOosProducts->getSelect()
			->joinLeft(
				array('order_item' => $coreResource->getTableName('sales/order_item')), "e.entity_id=order_item.product_id", array('outofstock_at' => 'MAX(IFNULL(order_item.created_at,e.created_at))'));
		//$listOosProducts->getSelect()->group('entity_id');
		$listOosProducts->groupByAttribute('entity_id');
		return  $listOosProducts;
	}
	
}