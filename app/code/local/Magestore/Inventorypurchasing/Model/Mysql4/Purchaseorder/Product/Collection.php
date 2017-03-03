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
class Magestore_Inventorypurchasing_Model_Mysql4_Purchaseorder_Product_Collection extends Magestore_Inventoryplus_Model_Mysql4_Collection_Abstract {

    protected $_isGroupSql = false;

    public function setIsGroupCountSql($value) {
        $this->_isGroupSql = $value;
        return $this;
    }

    public function _construct() {
        parent::_construct();
        $this->_init('inventorypurchasing/purchaseorder_product');
    }

    public function groupBy($field) {
        $this->getSelect()->group($field);
    }

    public function having($having) {
        $this->getSelect()->having($having);
    }

    public function addColumns($columns) {
        $this->getSelect()->columns($columns);
    }
    
    /**
     * 
     * @param int $supplierId
     * @param string $firstDate
     * @param mixed $group
     * @return \Magestore_Inventorypurchasing_Model_Mysql4_Purchaseorder_Product_Collection
     */
    public function getBySupplier($supplierId, $firstDate, $group = null) {
        $this->getSelect()
                ->joinLeft(array('purchaseorder' => $this->getTable('inventorypurchasing/purchaseorder')), "main_table.purchase_order_id = purchaseorder.purchase_order_id", array('purchase_on'));
        $this->addFieldToFilter('purchase_on', array('gteq' => $firstDate));
        $this->getSelect()->where("`purchaseorder`.`supplier_id`= $supplierId");
        $this->getSelect()->where("`purchaseorder`.`status` = 6");
        $this->getSelect()->columns(array('date_without_hour' => 'date(`purchase_on`)'));
        $this->getSelect()->columns(array('purchase_qty_by_day' => 'sum(`main_table`.`qty_recieved`)'));
        $this->getSelect()->columns(array('cost_purchase_by_day' => 'sum(`purchaseorder`.`total_amount`)'));
        if($group) {
            $this->getSelect()->group($group);
        }
        return $this;
    }
    
    /**
     * 
     * @param array $productIds
     * @return \Magestore_Inventorypurchasing_Model_Mysql4_Purchaseorder_Product_Collection
     */
    public function getLastSupplierProduct($productIds) {
        $this->addFieldToFilter('product_id', array('in'=> $productIds))
                            ->setOrder('purchase_order_product_id', 'DESC');
        $this->getSelect()->join(array('po' => $this->getTable('inventorypurchasing/purchaseorder')),
                                        'main_table.purchase_order_id = po.purchase_order_id',
                                        array('supplier_id'));
        $this->getSelect()->group('main_table.product_id');
        $this->getSelect()->columns(array(
            'list_supplier' => new Zend_Db_Expr('GROUP_CONCAT(`supplier_id` SEPARATOR ",")'),
        ));
        return $this;        
    }

}
